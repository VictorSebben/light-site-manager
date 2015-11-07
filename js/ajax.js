function createRequest() {
    var request;

    try {
        request = new XMLHttpRequest();
    } catch ( e ) {
        request = null;
    }

    return request;
}

// Generic function to handle ajax requests,
// based on John Resig's teachings on
// Pro JavaScript Techniques
function ajax( opts ) {
    // load default values into opts object
    // if values were not provided by the user application
    opts = {
        type: opts.type || "POST",
        url: opts.url || "",
        timeout: opts.timeout || 5000,

        // callback functions that will get called
        // on success, failure, or upon
        // completion (success or failure)
        onComplete: opts.onComplete || function(){},
        onFail: opts.onFail || function(){},
        onSuccess: opts.onSuccess || function(){},

        // data to be sent to the server in POST requests
        data: opts.data || "",

        // data type that will be returned by the server
        dataType: opts.dataType
    };

    // get request object
    var request = createRequest();

    if ( ! request ) {
        throw "Could't create request object!";
    }

    // open asynchronous connection
    request.open( opts.type, opts.url, true );

    // wait for a certain time before giving up
    var timeout = opts.timeout;

    // flag to keep track of when the request has been
    // successfully completed
    var requestDone = false;

    // after a certain time, initialize callback that will
    // cancel the request if it has not been completed yet
    setTimeout( function () {
        requestDone = true;
    }, timeout );

    // watch for changes in the state of the documents
    request.onreadystatechange = function () {
        // wait until the date is fully loaded,
        // and make sure that the request hasn't already timed out
        if ( request.readyState == 4 && ! requestDone ) {
            // check if request was successful
            if ( httpSuccess( request ) ) {
                // execute success callback
                opts.onSuccess( httpData( request, opts.type ) );
            } else {
                opts.onError();
            }

            // call the completion callback
            opts.onComplete();

            // avoid memory leaks
            request = null;
        }
    };

    // establish the conneciton to the server
    request.send();

    // function that determines the success of the HTTP response
    function httpSuccess( r ) {
        try {
            // if no server status is provided, and we're actually
            // requesting a local file, then it was successful
            return !r.status && location.protocol == "file:" ||

                // any status in the 200 range is good
                ( r.status >= 200 && r.status < 300 ) ||

                // successful if the document has not been modified
                r.status == 304 ||

                // Safari returns an empty status if the file has not been modified
                navigator.userAgent.indexOf( "Safari" ) >= 0
                    && typeof r.status == "undefined";
        } catch ( e ) {}

        // if checking the status failed, assume the request failed too
        return false;
    }

    // extract the correct data from the HTTP response
    function httpData( r, type ) {
        // Get the content-type header
        var ct = r.getResponseHeader( "content-type" );

        // if no default type was provided, determine if some
        // form of XML was returned from the server
        var data = !type && ct && ct.indexOf( "xml" ) >= 0;

        // get the XML Document object if XML was returned from
        // the server, otherwise return the text contents returned by
        // the server
        data = type == "xml" || data ? r.responseXML : r.responseText;

        // return the response data (either an XML document or a text string)
        return data;
    }
}
