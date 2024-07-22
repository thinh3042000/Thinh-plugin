    document.addEventListener('DOMContentLoaded', function() {
        let podcastItems = document.querySelectorAll('.podcast-item');
        let maxItems = podcastItems.length;
        for (let i = 1; i <= maxItems; i++) {
            let podcastId = i;
            let player = document.getElementById('podcast-audio-list-controls-' + podcastId);
            // console.log(player);
            let stopButton = document.getElementById('stop-button-' + podcastId);
            let progress = document.getElementById('progress-' + podcastId);
            let currentTimeDisplay = document.getElementById('current-time-' + podcastId);
            let totalTimeDisplay = document.getElementById('total-time-' + podcastId);
            let playImg = document.getElementById('play-icon-' + podcastId) ;
            let pauseImg = document.getElementById('play-icon-run-' + podcastId);
            let minutes = Math.floor(player.duration / 60);
            let seconds = Math.floor(player.duration % 60);
            totalTimeDisplay.textContent = `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;

            player.addEventListener('loadedmetadata', function() {
                if (!isNaN(player.duration)) {
                    progress.max = player.duration;
                    progress.value = player.currentTime;
                    updateCurrentTime(player.currentTime);
                    updateTotalTime(player.duration);
                }
            });

            function playPauseList() {
                console.log("ok");
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
            function pauseAllPlayers() {
                for (let j = 1; j <= maxItems; j++) {
                    if (j !== podcastId) {
                        let otherPlayer = document.getElementById('podcast-audio-list-controls-' + j);
                        let otherPlayImg = document.getElementById('play-icon-' + j);
                        let otherPauseImg = document.getElementById('play-icon-run-' + j);
                        if (!otherPlayer.paused) {
                            otherPlayer.pause();
                            otherPlayImg.style.display = 'inline';
                            otherPauseImg.style.display = 'none';
                        }
                    }
                }
            }
            player.addEventListener('timeupdate', function() {
                updateProgress();
                updateCurrentTime(player.currentTime);
            });

            progress.addEventListener('input', function() {
                player.currentTime = progress.value;
                updateCurrentTime(progress.value);
            });

            progress.addEventListener('click', function(e) {
                let percent = e.offsetX / this.offsetWidth;
                player.currentTime = percent * player.duration;
            });

            function updateProgress() {
                let currentTime = player.currentTime;
                let duration = player.duration;
                if (!isNaN(duration)) {
                    let percent = (currentTime / duration) * 100;
                    progress.value = percent;

                    progress.style.background = `linear-gradient(to right, #00AF5A 0%, #72e9af ${percent}%, #CCCCCC ${percent}%, #CCCCCC 100%)`;
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

            stopButton.addEventListener('click', function() {
                pauseAllPlayers();
                playPauseList();
            });
        }
    });
