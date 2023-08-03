function loadContent(template, id) {
    let url;
    if(typeof id === "number") {
        url = Routing.generate(template, {id: id});
    } else {
        url = Routing.generate(template);
    }
    $.ajax({
        url: url,
    })
        .done(function (data) {
            $('main').html(data);
            window.history.pushState(data,data.title, url);
        });
}

window.onpopstate = function (event) {
    $('main').html(event.state);
}

function loadResults() {
    let search = $('#search').val();
    if(search.length < 3) {
        $('#results').html('');
        return;
    }
    let url = Routing.generate('results');
    $.ajax({
        url: url,
        type: 'POST',
        data: {search: search},
    })
        .done(function (data) {
            $('#results').html(data);
        });
}