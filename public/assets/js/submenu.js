{
    function openSubmenu(track) {
        for(let sub of document.getElementsByClassName('submenu')) {
            if(sub === track)
                continue;
            sub.style.display = 'none';
        }
        if(getComputedStyle(track).getPropertyValue('display') === 'none') {
            track.style.display = 'block';
        } else {
            track.style.display = 'none';
        }
    }

    function loadEvents() {
        for(let track of document.getElementsByClassName('submenu')) {
            track.parentNode.addEventListener('click', (event) => {
                openSubmenu(track);
                event.stopPropagation();
            });
        }
    }
}