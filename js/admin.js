const admin_promise = new Promise((resolve, reject) => {
    $.ajax({
        type: "POST",
        url: "/api/sets-api.php",
        data: {
            api:"admin", act:"is_admin"
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

async function admin_check() {
    value = await admin_promise;
    return value;
}