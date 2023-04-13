let player;
let isPlaying = false;

function play(url) {
    player = AV.Player.fromURL(url);
    player.play();
    player.on('end', function () {
        play('/music/Music/Trivium/(2017) The Sin And The Sentence [Hi-Res]/01. The Sin And The Sentence.flac');
    });
}

function play_pause(url) {
    if(isPlaying) {
        isPlaying = false;
        player.stop();
    } else {
        isPlaying = true;
        play(url);
    }
}

