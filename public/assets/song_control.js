let song = new Howl({
    src: []
});
let isPlaying = false;

function play(url) {
    /*const response = await axios.get("/music/Music" + url, {
        responseType: 'arraybuffer',
    });
    // create audioBuffer (decode audio file)
    const audioBuffer = await audioContext.decodeAudioData(response.data);*/

    song = new Howl({
        src: [url],
        html5: true,
    });
    song.play();
}

function play_pause(url) {
    if(isPlaying) {
        isPlaying = false;
        song.stop();
    } else {
        isPlaying = true;
        play(url);
    }
}

