<div class="row">
    <div class="col-md-3">
        <button type="button" class="btn btn-block btn-primary log_date_pre">{{__("Önceki")}}</button>
    </div>
    <div class="col-md-6">
        <button type="button" class="btn btn-default btn-block log_date_text">{{__("Tarih Seç")}}</button>
    </div>
    <div class="col-md-3">
        <button type="button" class="btn btn-block btn-primary log_date_next">{{__("Sonraki")}}</button>
    </div>
</div>
<br>

<div class="table-responsive mirrorLogTable">
    <div class="overlay">
        <div class="spinner-border" role="status">
            <span class="sr-only">{{__('Loading')}}...</span>
        </div>
    </div>
</div> 

<script>
    logDatesInfo = {
        index: 0,
        dates: []
    };

    function setLogDates(data) {
        logDatesInfo = data;
        $('.log_date_text').text(logDatesInfo.dates[logDatesInfo.index]);
        getLog();

        if(logDatesInfo.index === 0){
            $('.log_date_pre').attr("disabled", true);
        }else{
            $('.log_date_pre').attr("disabled", false);
        }

        if(logDatesInfo.index === logDatesInfo.dates.length - 1){
            $('.log_date_next').attr("disabled", true);
        }else{
            $('.log_date_next').attr("disabled", false);
        }

        $('.log_date_text').datepicker('destroy');
        $('.log_date_text').datepicker({
            format: 'yyyy-mm-dd',
            beforeShowDay: function(date)
            {
                var dd = (date.getDate() < 10 ? '0' : '') 
                        + date.getDate(); 
                          
                var MM = ((date.getMonth() + 1) < 10 ? '0' : '') 
                        + (date.getMonth() + 1); 
                if ($.inArray(date.getFullYear() + '-' + MM + '-' + dd, logDatesInfo.dates) !== -1)
                {
                    return;
                }
                return false;
            }
        })
        .off('changeDate')
        .on('changeDate', function(ev){
            setLogDates({
                index: $.inArray(ev.format(), logDatesInfo.dates),
                dates: logDatesInfo.dates
            });
            $('.log_date_text').datepicker('hide');
        });
    }

    function init_log_dates(data) {
        setLogDates({
            index: data.length - 1,
            dates: data
        });
        $('.log_date_pre').off('click');
        $('.log_date_pre').click(function(){
            pre();
        });
        $('.log_date_next').off('click');
        $('.log_date_next').click(function(){
            next();
        });
    }

    function pre() {
        setLogDates({
            index: logDatesInfo.index - 1,
            dates: logDatesInfo.dates
        });
    }

    function next() {
        setLogDates({
            index: logDatesInfo.index + 1,
            dates: logDatesInfo.dates
        });
    }

    function first() {
        setLogDates({
            index: 0,
            dates: logDatesInfo.dates
        });
    }

    function last() {
        setLogDates({
            index: logDatesInfo.dates.length - 1,
            dates: logDatesInfo.dates
        });
    }

    function getLogDates(){
        showSwal('{{__("Yükleniyor...")}}','info',2000);
        request(API("get_log_dates"), new FormData(), function(response) {
            let dates = JSON.parse(response).message;
            if(dates.length){
                init_log_dates(dates);
            }
            Swal.close();
        }, function(response){
            let error = JSON.parse(response);
            showSwal(error.message,'error',2000);
        });
    }

    function getLog(){
        showSwal('{{__("Yükleniyor...")}}','info',2000);
        let form = new FormData();
        form.append("date", logDatesInfo.dates[logDatesInfo.index]);
        request(API('get_log'), form, function (response) {
            $('.mirrorLogTable').html(response).find('table').DataTable({
                bFilter: true,
                "language" : {
                    url : "/turkce.json"
                }
            });
            Swal.close();
        }, function(response){
            let error = JSON.parse(response);
            showSwal(error.message,'error',2000);

        })
    }
</script>