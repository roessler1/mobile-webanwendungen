//audio element related tasks
{
    let audio;
    let timeUpdate;

    function loadTrack(blob) {
        if(!audio) {
            audio = new Audio();
            audio.addEventListener("ended", () => {
                clearInterval(timeUpdate);
                playNext();
            });
            let play = document.getElementById('play');
            play.setAttribute('d', getComputedStyle(play).getPropertyValue('--playing'))
        }
        timeUpdate = setInterval(updateProgress, 50);
        audio.type = blob.type;
        audio.src = URL.createObjectURL(blob);
        audio.play();
    }

    const updateProgress = () => {
        document.getElementById("active-slider").style.width = ((audio.currentTime * 100) / audio.duration) + "%";
        document.getElementById('slider-pos').value = audio.currentTime;
        let min = Math.floor(audio.currentTime/60);
        let sec = Math.floor(audio.currentTime%60);
        if(sec < 10)
            sec = "0"+sec;
        document.getElementById("counter").innerHTML = min + ":" + sec;
    }

    function setCurrentTime() {
        let val = document.getElementById('slider-pos').value;
        audio.currentTime = val;
    }

    function suspend() {
        audio.pause();
        let button = document.getElementById("play");
        let path = getComputedStyle(button).getPropertyValue("--paused");
        button.setAttribute("d", path);
        clearInterval(timeUpdate);
    }

    function resume() {
        timeUpdate = setInterval(updateProgress, 50);
        audio.play();
        let button = document.getElementById("play");
        let path = getComputedStyle(button).getPropertyValue("--playing");
        button.setAttribute("d", path);
    }

    function togglePlayback() {
        if(audio.paused) {
            resume();
        } else {
            suspend();
        }
    }

    function hasFinished() {
        return audio.ended;
    }

    function muteAudio() {
        !audio.muted;
    }
}

//media session related tasks
{
    let mediaSession = null;

    const initMediaSession = function () {
        if(mediaSession instanceof MediaSession) {
            return;
        }
        if('mediaSession' in navigator) {
            mediaSession = navigator.mediaSession;
            mediaSession.setActionHandler('play', resume);
            mediaSession.setActionHandler('pause', suspend);
        }
    }

    function setMetadata(track) {
        if(!mediaSession) {
            initMediaSession();
        }
        if(!mediaSession) {
            return;
        }
        mediaSession.metadata = new MediaMetadata({
            title: track.name,
            album: track.album.name,
            artist: track.artist.name,
            artwork: [{src: track.album.cover, type: 'image/jpeg'}]
        });
        document.getElementById('artist').innerHTML = track.artist.name;
        document.getElementById('track').innerHTML = track.name;
        let min = Math.floor(track.duration/60);
        let sec = track.duration%60;
        if(sec < 10)
            sec = "0"+sec;
        document.getElementById('duration').innerHTML = min + ":" + sec;
    }

    function setPrevious() {
        if(!mediaSession)
            return;
        mediaSession.setActionHandler('previoustrack', previous);
    }

    function unsetPrevious() {
        if(!mediaSession)
            return;
        mediaSession.setActionHandler('previoustrack', null);
    }

    function setNext() {
        if(!mediaSession)
            return;
        mediaSession.setActionHandler('nexttrack', next);
    }

    function unsetNext() {
        if(!mediaSession)
            return;
        mediaSession.setActionHandler('nexttrack', null);
    }
}

//queue related tasks
{
    const mp3_pattern = [0x49, 0x44, 0x33];
    const flac_pattern = [0x66, 0x4C, 0x61, 0x43];

    let queue = [];
    let loadedBuffers = [];
    let currentTrack = 0;
    let jumper = 0;
    let mode = 0;
    let timer;
    let random = false;

    function setQueue(tracks, idx) {
        for(let i = 0; i < tracks.length; i++) {
            tracks[i].nr = i;
        }
        queue = tracks;
        currentTrack = idx;
        loadedBuffers.length = 0;
        jumper = 0;
        if(random)
            shuffleQueue();
        fetchTrack(queue[currentTrack].path).then((arrayBuffer) => {
            loadedBuffers[1] = arrayBuffer;
            setTrack();
        }).then(() => {
            loadSurrounded();
        });
    }

    function expandQueue(track) {
        if(queue.length === 0) {
            setQueue([track], 0);
            return;
        }
        track.nr = queue.length;
        queue.push(track);
        if(currentTrack === queue.length - 2) {
            fetchTrack(track.path).then((nextBuffer) => {
                loadedBuffers[2] = nextBuffer;
            });
        }
        if(currentTrack === 0 && mode === 1) {
            fetchTrack(track.path).then((lastBuffer) => {
                loadedBuffers[0] = lastBuffer;
            });
        }
    }

    function addNext(track) {
        if(queue.length === 0) {
            setQueue([track], 0);
            return;
        }
        track.nr = currentTrack+1;
        fetchTrack(track.path).then((nextBuffer) => {
            loadedBuffers[2] = nextBuffer;
            queue.splice(currentTrack+1, 0, track);
            if(hasFinished()) playNext();
        });
        for (const queuedTrack in queue) {
            if(queuedTrack.nr > currentTrack) queuedTrack.nr = queuedTrack.nr+1;
        }
    }

    function playNext() {
        if(mode === 2)
            setTrack();
        else {
            if(currentTrack+jumper < queue.length-1 || mode === 1)
                jumper++;
            shiftHandler();
        }
    }

    function previous() {
        clearTimeout(timer);
        if(currentTrack+jumper > 0 || mode === 1)
            jumper--;
        timer = setTimeout(shiftHandler, 1000);
    }

    function next() {
        clearTimeout(timer);
        if(currentTrack+jumper < queue.length-1 || mode === 1)
            jumper++;
        timer = setTimeout(shiftHandler, 1000);
    }

    const shiftHandler = function () {
        if(jumper > 0)
            nextTrack();
        else if(jumper < 0)
            previousTrack();
    }

    const previousTrack = async function () {
        const offset = jumper;
        jumper = 0;
        let track = currentTrack+offset;
        if(mode === 1 && track < 0)
            track += queue.length;
        currentTrack += offset;
        if(currentTrack < 0 && mode === 1)
            currentTrack += queue.length;
        if(offset === -1) {
            loadedBuffers.pop();
            if(track === 0) {
                if(mode === 1)
                    loadedBuffers.unshift(await fetchTrack(queue[queue.length-1].path));
                else
                    loadedBuffers.unshift(null);
            }
            else
                loadedBuffers.unshift(await fetchTrack(queue[track-1].path));
            setTrack();
        } else if(offset < -1) {
            loadedBuffers.pop();
            loadedBuffers.pop();
            loadedBuffers.unshift(await fetchTrack(queue[track].path));
            loadedBuffers.unshift(null);
            setTrack();
            if(track === 0 && mode === 1)
                loadedBuffers[0] = await fetchTrack(queue[queue.length-1].path);
            else if(track > 0)
                loadedBuffers[0] = await fetchTrack(queue[track-1].path);
            if(offset < -2) {
                if (track === queue.length - 1 && mode === 1)
                    loadedBuffers[2] = await fetchTrack(queue[0].path);
                else if (track < queue.length - 1)
                    loadedBuffers[2] = await fetchTrack(queue[track + 1].path);
            }
        }
    }

    const nextTrack = async function () {
        const offset = jumper;
        jumper = 0;
        let track = currentTrack+offset;
        if(mode === 1 && track > queue.length-1)
            track -= queue.length;
        currentTrack += offset;
        if(currentTrack > queue.length-1 && mode === 1)
            currentTrack -= queue.length;
        if(offset === 1) {
            loadedBuffers.shift();
            setTrack();
            if(track === queue.length-1) {
                if(mode === 1)
                    loadedBuffers.push(await fetchTrack(queue[0].path));
                else
                    loadedBuffers.push(null);
            }
            else
                loadedBuffers.push(await fetchTrack(queue[track+1].path));
        } else if(offset > 1) {
            loadedBuffers.shift();
            loadedBuffers.shift();
            loadedBuffers.push(await fetchTrack(queue[track].path));
            setTrack();
            loadedBuffers.push(null);
            if(offset > 2) {
                if (track === 0 && mode === 1)
                    loadedBuffers[0] = await fetchTrack(queue[queue.length-1].path);
                else if (track > 0)
                    loadedBuffers[0] = await fetchTrack(queue[track - 1].path);
            }
            if(track === queue.length-1 && mode === 1)
                loadedBuffers[2] = await fetchTrack(queue[0].path);
            else if(track < queue.length-1)
                loadedBuffers[2] = await fetchTrack(queue[track+1].path);
        }
    }

    const fetchTrack = function (src) {
        const originalConsole = console.log;
        console.log = function () {}
        return fetch(src).then((response) => {
            console.log = originalConsole;
            return response.arrayBuffer();
        }).then((arrayBuffer) => {
            return arrayBuffer;
        });
    }

    const setTrack = function () {
        if(isMP3(new Uint8Array(loadedBuffers[1].slice(0, 3))))
            loadTrack(new Blob([loadedBuffers[1]], {type: 'audio/mpeg'}));
        else if(isFlac(new Uint8Array(loadedBuffers[1].slice(0, 4)))) {
            if(new Audio().canPlayType('audio/flac') === "")
                return;
            loadTrack(new Blob([loadedBuffers[1]], {type: 'audio/flac'}));
        } else
            return;
        document.getElementById('slider-pos').setAttribute("max", queue[currentTrack].duration);
        setMetadata(queue[currentTrack]);
        if(currentTrack === 0)
            unsetPrevious();
        else
            setPrevious();
        if(currentTrack === queue.length-1)
            unsetNext();
        else
            setNext();
    }

    const isMP3 = function (head) {
        return mp3_pattern.every((value, index) => head[index] === value);
    };

    const isFlac = function (head) {
        return flac_pattern.every((value, index) => head[index] === value);
    };

    function changeMode() {
        let button = document.getElementById("repeat_mode");
        if(mode === 2) {
            mode = 0;
            button.setAttribute("d", getComputedStyle(button).getPropertyValue("--no_repeat"));
        } else {
            mode++;
            if(mode === 1)
                button.setAttribute("d", getComputedStyle(button).getPropertyValue("--all_repeat"));
            else {
                button.setAttribute("d", getComputedStyle(button).getPropertyValue("--one_repeat"));
            }
        }
        if(mode === 1) {
            if(currentTrack === 0) {
                fetchTrack(queue[queue.length - 1].path).then((lastBuffer) => {
                    loadedBuffers[0] = lastBuffer;
                });
            }
            if(currentTrack === queue.length-1) {
                fetchTrack(queue[0].path).then((nextBuffer) => {
                    loadedBuffers[2] = nextBuffer;
                })
            }
        } else {
            if(currentTrack === 0)
                loadedBuffers[0] = null;
            if(currentTrack === queue.length-1)
                loadedBuffers[2] = null;
        }
    }

    const shuffleQueue = function () {
        let temp = queue[currentTrack];
        queue[currentTrack] = queue[0];
        queue[0] = temp;
        currentTrack = 0;
        for(let i = 1; i < queue.length-1; i++) {
            let rand = Math.floor(Math.random() * (queue.length-i) + i);
            temp = queue[rand];
            queue[rand] = queue[i];
            queue[i] = temp;
        }
    }

    function shuffle() {
        if(random) {
            random = false;
            if(queue.length > 0) {
                currentTrack = queue[currentTrack].number-1;
            }
            queue.sort(function(a, b) {
                return a.nr - b.nr;
            });
            document.getElementById("track_order").style.fill = '#ffffff';
            if(loadedBuffers.length === 3)
                loadSurrounded(currentTrack);
        } else {
            random = true;
            shuffleQueue();
            document.getElementById("track_order").style.fill = '#ff5a00';
            if(loadedBuffers.length === 3)
                loadSurrounded(currentTrack);
        }
    }

    const loadSurrounded = function () {
        let pos;
        if(currentTrack === 0 && mode === 1)
            pos = queue.length-1;
        else if(currentTrack > 0)
            pos = currentTrack-1;
        else
            pos = null;
        if(pos === null)
            loadedBuffers[0] = null;
        else {
            fetchTrack(queue[pos].path).then((lastBuffer) => {
                loadedBuffers[0] = lastBuffer;
            });
        }
        if(currentTrack === queue.length-1 && mode === 1)
            pos = 0;
        else if(currentTrack < queue.length-1)
            pos = currentTrack+1;
        else
            pos = null;
        if(pos === null)
            loadedBuffers[2] = null;
        else {
            fetchTrack(queue[pos].path).then((nextBuffer) => {
                loadedBuffers[2] = nextBuffer;
            })
        }
    }
}

document.onreadystatechange = function () {
    if(document.readyState === 'complete') {
        window.history.replaceState($('main').html(), document.title, document.documentURI);
        document.getElementById('lastbtn').addEventListener('click', previous);
        document.getElementById('nextbtn').addEventListener('click', next);
        document.getElementById('playbtn').addEventListener('click', togglePlayback);
        document.getElementById('track_repeat').addEventListener('click', changeMode);
        document.getElementById('track_order').addEventListener('click', shuffle);
        document.getElementById('slider-pos').addEventListener('input', setCurrentTime);
        document.getElementById('track_volume').addEventListener('click', muteAudio);
    }
}