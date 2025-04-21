const auth_promise = new Promise((resolve, reject) => {
    $.ajax({
        type: "POST",
        url: "/api/sets-api.php",
        data: {
            api:"user", act:"auth_check",
        },
        xhrFields: {
            withCredentials: true
        },
    }).done(function( result )
    {
        console.log(result['status']);
        //console.log(result['status']==='ok');
        if(result['status']==='ok') {
            resolve(result['message']);
        } else {
            resolve(false);
        }
    });
});

async function auth_check() {
    value = await auth_promise;
    return value;
}

const operator_promise = new Promise((resolve, reject) => {
    $.ajax({
        type: "POST",
        url: "/api/sets-api.php",
        data: {
            api:"operator", act:"is_operator"
        },
        xhrFields: {
            withCredentials: true
        },
    }).done(function( result )
    {
        console.log(result);
        //console.log(result['status']==='ok');
        if(result['status']==='ok') {
            resolve(result.data.response);
        } else {
            resolve(false);
        }
    });
});

async function operator_check() {
    value = await operator_promise;
    return value;
}