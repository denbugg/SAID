const irregular_operator_promise = new Promise((resolve, reject) => {
    $.ajax({
        type: "POST",
        url: "/api/sets-api.php",
        data: {
            api:"operator", act:"is_irregular_operator"
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

async function irregular_operator_check() {
    value = await irregular_operator_promise;
    return value;
}