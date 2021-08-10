<div class="info-box shadow-sm log-box border">
    <div class="overlay"> <i class="fas fa-3x fa-sync-alt fa-spin"></i> </div>
    <div class="info-box-content p-0" style="background:black; height:500px; overflow-y:scroll;">
        <pre id="detail_log" style="color:lime; white-space:pre-wrap; word-wrap:break-word;">
        </pre>
    </div>
</div>


<script>
    
    function getLastMirrorLog(){
        $('.log-box').find('.overlay').show();
        request(API('get_last_mirror_log'), new FormData(), function (response) {
            const output = JSON.parse(response).message;
            $('#detail_log').html(output).parent().scrollTop(999999999999);
            $('.log-box').find('.overlay').hide();
        }, function(response){
            const error = JSON.parse(response).message;
            showSwal(error,'error',2000);
        })
    }

</script>
