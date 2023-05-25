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

document.onreadystatechange = () => {
    if(document.readyState === "complete") {
        window.history.replaceState($('main').html(), document.title, document.documentURI);
    }
}

window.onpopstate = function (event) {
    $('main').html(event.state);
}