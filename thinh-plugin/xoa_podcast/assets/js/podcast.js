	document.addEventListener('DOMContentLoaded', function() {
    let player = document.getElementById('podcast-audio-player');
    let rewindButton = document.getElementById('rewind-button');
    let playImg = document.getElementById('play-img');
    let pauseImg = document.getElementById('pause-img');
    let forwardButton = document.getElementById('forward-button');
    let stopButton = document.getElementById('stop-button');
    let resetButton = document.getElementById('reset-button');
    let progress = document.getElementById('progress');
    let currentTimeDisplay = document.getElementById('current-time');
    let totalTimeDisplay = document.getElementById('total-time');
	let minutes = Math.floor(player.duration / 60);
    let seconds = Math.floor(player.duration % 60);
    totalTimeDisplay.textContent = `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
    player.onloadedmetadata = function() {
        if (!isNaN(player.duration)) {
            progress.max = player.duration;
            updateTotalTime(player.duration);
        }
        progress.value = player.currentTime;
        updateCurrentTime(player.currentTime);
    };

    function playPause() {
        if (player.paused) {
            player.play();
            playImg.style.display = 'none';
            pauseImg.style.display = 'inline';
        } else {
            player.pause();
            playImg.style.display = 'inline';
            pauseImg.style.display = 'none';
        }
    }

    player.addEventListener('timeupdate', function() {
        updateProgress();
        updateCurrentTime(player.currentTime);
    });

    progress.oninput = function() {
        player.currentTime = progress.value;
        updateCurrentTime(progress.value);
    };

    progress.onclick = function(e) {
        let percent = e.offsetX / this.offsetWidth;
        player.currentTime = percent * player.duration;
    };

    rewindButton.onclick = function() {
        player.currentTime -= 15;
    };

    forwardButton.onclick = function() {
        player.currentTime += 15;
    };

    resetButton.onclick = function() {
        player.currentTime = 0;
        progress.value = 0;
        player.pause();
        playImg.style.display = 'inline';
        pauseImg.style.display = 'none';
    };

    function updateProgress() {
        let currentTime = player.currentTime;
        let duration = player.duration;
        if (!isNaN(duration)) {
            let percent = (currentTime / duration) * 100;
            progress.value = percent;

            progress.style.background = `linear-gradient(to right,  #00AF5A 0%, #72e9af ${percent}%, #CCCCCC ${percent}%, #CCCCCC 100%)`;
        }
    }

    function updateCurrentTime(currentTime) {
        let minutes = Math.floor(currentTime / 60);
        let seconds = Math.floor(currentTime % 60);
        currentTimeDisplay.textContent = `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
    }

    function updateTotalTime(duration) {
        let minutes = Math.floor(duration / 60);
        let seconds = Math.floor(duration % 60);
        totalTimeDisplay.textContent = `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
    }

    stopButton.onclick = function() {
        playPause(); 
    };

    let loveButton = document.querySelector('.love-button'); 

    loveButton.addEventListener('click', function() {
        loveButton.classList.toggle('button-container'); 
        loveButton.classList.toggle('active');
    });
});
