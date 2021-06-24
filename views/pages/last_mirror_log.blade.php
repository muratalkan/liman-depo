<div class="detail_div" style="background-color:black;">
    <code class="detail_log" style="color:lime;">
        <div class="overlay">
            <div class="spinner-border" role="status">
                <span class="sr-only">{{__('Loading')}}...</span>
            </div>
        </div>
    </code>
</div>

<script>
    function getLastMirrorLog(){
        $('.modal').modal('hide');
        showSwal('{{__("Yükleniyor...")}}','info',2000);
        let form = new FormData();
        request(API('get_last_mirror_log'), form, function (res) {
            text = JSON.parse(res).message;
            text = text.reverse()
            html = ""
            if(text.length <= 1)[
                html = "{{__('Sonuç yok!')}}"
            ]
            else{
                for(i = 0; i < text.length; i++){
                    html += text[i]+"<br>"
                }
            }

            $('.detail_log').html(html);
            Swal.close();
        }, function(res){
            let error = JSON.parse(res);
            showSwal(error.message,'error',2000);
        })
    }
</script>
