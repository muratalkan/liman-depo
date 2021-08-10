<form id="frm_packageSearchTab" onsubmit="return getPackageSearchResult()">
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <h5>{{__("Depo Türü")}}</h5>
                <div class="select_repoType">
                    <div class="overlay">
                        <div class="spinner-border" role="status"></div>
                    </div>
                    <div>
                        <select class="form-control select2" name="select_repoType" required> 
                            <option value="externalRepo" >Aynalama Deposu</option>
                            <option value="internalRepo">Yerel Depo</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <h5>{{__("Depo Listesi")}}</h5>
                <div class="select_repoList">
                    <div class="overlay">
                        <div class="spinner-border" role="status"></div>
                    </div>
                    <div>
                        <select class="form-control select2" name="select_repoList" required> </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <h5>{{__("Depo Adresi")}}</h5>
                <div class="select_repoUrl">
                    <div class="overlay">
                        <div class="spinner-border" role="status"></div>
                    </div>
                    <div>
                        <select class="form-control select2" name="select_repoUrl" required></select>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <h5>{{__("Depo Adı")}}</h5>
                <div class="select_repoName">
                    <div class="overlay">
                        <div class="spinner-border" role="status"></div>
                    </div>
                    <div>
                        <select class="form-control select2" name="select_repoName" required> </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <h5>{{__("Kod Adı")}}</h5>
                <div class="select_repoCodename">
                    <div class="overlay">
                        <div class="spinner-border" role="status"></div>
                    </div>
                    <div>
                        <select class="form-control select2" name="select_repoCodename" required> </select> 
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <h5>{{__("Paket")}}</h5>
                <div class="select_repoPackage">
                    <div class="overlay">
                        <div class="spinner-border" role="status"></div>
                    </div>
                    <div>
                        <select class="form-control select2" name="select_repoPackage"> </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <h5>{{__("Paket Adı")}}</h5>
    <input type="text" class="form-control" name="packagename" placeholder="{{__('Paket Adını Giriniz')}}" ><br>
    <button type="submit" class="btn btn-success">{{__("Paket Ara")}}</button><br><br>
</form>

<div id="packagesTable" class="table-responsive"></div>

<script>
    var SELECT_NAME_ARRAY = ['select_repoType', 'select_repoList', 'select_repoUrl', 'select_repoName', 'select_repoCodename', 'select_repoPackage', 'select_repoArch'];
    var STORAGE_ARRAY=[], REPO_URLS=[], REPOS=[], REPO_VERSION=[];
    var REPO_TYPE='';

    function initializePackageSearch(){
        $('select[name=select_repoType]').val('externalRepo');
        $('#packagesTable').find(".overlay").hide();
        SELECT_NAME_ARRAY.forEach(function (attrName) {
            setSelectbox(attrName, []);
            hideLoadingEffect(attrName);
        });

        REPO_TYPE = 'externalRepo';
        resetOtherSelectBox('select_repoType');
        showLoadingEffect('select_repoType');
        getMirrorNames();
        $('#packagesTable').html('');
        $('input[name=packagename]').val('');
    }

    function getPackageSearchResult(){
        if(REPO_TYPE === 'externalRepo'){ //mirrorRepo
            getMirrorSearchResult();
        }else{
            getInternalRepoResult();
        }
        return false;
    }

    function getMirrorSearchResult(currentPage=1){
        showSwal('{{__("Yükleniyor...")}}', 'info', 2000);
        $('#packagesTable').html(`
            <div class="overlay">
                <div class="spinner-border" role="status">
                    <span class="sr-only">{{__('Loading')}}...</span>
                </div>
            </div>
        `);
        let form = new FormData();
            form.append("currentPage", currentPage);
            form.append("storagePath", getStoragePath($('select[name=select_repoList]').val()));
            $('#frm_packageSearchTab').find("select").each(function(){
                form.append($(this).attr('name'), $(this).val());
            });
            $('#frm_packageSearchTab').find("input").each(function(){
                form.append($(this).attr('name'), $(this).val());
            });
        request(API('get_mirror_search_result'), form, function (response) {
            let json = JSON.parse(response).message;
            $('#packagesTable').html(json).find('.table').addClass('table-striped');
            setLinkStatus();
            Swal.close();
        }, function(response){
            const error = JSON.parse(response).message;
            $('#packagesTable').html(`
                <div class="alert alert-danger text-center" role="alert">
                    <strong>${error}</strong>
                </div>`)
        })
    }

    function getInternalRepoResult(currentPage=1){
        showSwal('{{__("Yükleniyor...")}}', 'info', 2000);
        $('#packagesTable').html(`
            <div class="overlay">
                <div class="spinner-border" role="status">
                    <span class="sr-only">{{__('Loading')}}...</span>
                </div>
            </div>
        `);
        let form = new FormData();
            form.append("currentPage", currentPage);
            form.append("internalRepoName", $('select[name=select_repoList]').val());
            form.append("packagename", $('input[name=packagename]').val());
        request(API('get_internalRepo_search_result'), form, function (response) {
            let json = JSON.parse(response).message;
            $('#packagesTable').html(json).find('.table').addClass('table-striped');
            setLinkStatus();
            Swal.close();
        }, function(response){
            const error = JSON.parse(response).message;
            $('#packagesTable').html(`
                <div class="alert alert-danger text-center" role="alert">
                    <strong>${error}</strong>
                </div>`)
        })
    }

    function getMirrorNames(){
        request(API('get_mirror_names'), new FormData(), function (response) {
            const data = JSON.parse(response).message;
            STORAGE_ARRAY = data,
            setSelectbox('select_repoList', data);
        }, function(response){
            const error = JSON.parse(response).message;
            showSwal(error, 'error', 2000);
        })
    }

    function getMirrorList(storagePath, mirrorName){
        let form = new FormData();
            form.append("storagePath", storagePath);
            form.append("mirrorName", mirrorName);
        return new Promise(function(resolve, reject){
            request(API('get_mirror_list'), form, function (response) {
                const data = JSON.parse(response).message;
                REPO_URLS = data.external_repo_urls;
                resolve();
            },function(response){
                let error = JSON.parse(response).message;
                showSwal(error, 'error', 2000);
                resolve();
            })
        });
    }

    function getInternalRepos(){
        request(API('get_internal_repo_names'), new FormData(), function (response) {
            const data = JSON.parse(response).message;
            setSelectbox('select_repoList', data);
        }, function(response){
            const error = JSON.parse(response).message;
            showSwal(error, 'error', 2000);
        })
    }

    $('select[name=select_repoType]').off('change').change(function(){
        resetOtherSelectBox('select_repoType');
        showLoadingEffect('select_repoType');
        REPO_TYPE = $('select[name=select_repoType]').val();
        if(REPO_TYPE === 'externalRepo'){ //mirrorRepo
            getMirrorNames();
        }else{
            getInternalRepos();
        }
    });
    
    $('select[name=select_repoList]').off('change').change(function(){
        const repoList = $('select[name=select_repoList]').val();
        if(REPO_TYPE === 'externalRepo'){ //mirrorRepo
            resetOtherSelectBox('select_repoList');
            showLoadingEffect('select_repoList');
            setRequiredSelects('select_repoList', true);
            let data = [];
            const storagePath = getStoragePath(repoList);
            getMirrorList(storagePath, repoList).then(function(intentsArr){
                REPO_URLS.forEach(function (item) {
                    data.push({id:item.external_repo_url, text:item.external_repo_url});
                });
                setSelectbox('select_repoUrl', data);
            },function(err){})
        }else{
            setRequiredSelects('select_repoList', false);
        }
    });

    $('select[name=select_repoUrl]').off('change').change(function(){
        resetOtherSelectBox('select_repoUrl');
        showLoadingEffect('select_repoUrl');
        let data = [];
        const repoUrl = $('select[name=select_repoUrl]').val();
            for (i = 0; REPO_URLS.length; i++) {
                if(REPO_URLS[i].external_repo_url === repoUrl){
                    REPOS = REPO_URLS[i].external_repos;
                    REPO_URLS[i].external_repos.forEach(function (item) {
                        data.push({id:item.external_repo_name, text:item.external_repo_name});
                    });
                    break;
                }
            }
        setSelectbox('select_repoName', data);
    });

    $('select[name=select_repoName]').off('change').change(function(){
        resetOtherSelectBox('select_repoName');
        showLoadingEffect('select_repoName');
        let data = [];
        const repoName = $('select[name=select_repoName]').val();
            for (i = 0; REPOS.length; i++) {
                if(REPOS[i].external_repo_name === repoName){
                    REPOS[i].versions.forEach(function (item) {
                        REPO_VERSION = item;
                        data.push({id:item.code_name, text:item.code_name});
                    });
                    break;
                }
            }
        setSelectbox('select_repoCodename', data);
    });

    $('select[name=select_repoCodename]').off('change').change(function(){
        resetOtherSelectBox('select_repoCodename');
        showLoadingEffect('select_repoCodename');
        const codeName = $('select[name=select_repoCodename]').val();
        let data = REPO_VERSION.packages.split(" ");
        data.push({id:'All', text:'All'});
        data.reverse();
        setSelectbox('select_repoPackage', data);
    });

    $('select[name=select_repoPackage]').off('change').change(function(){
        const repoPackage = $('select[name=searchMirror_slct_extRepoPackage]').val();
        data = [];
        data.push({id:'All', text:'All'});
        data.reverse();
        setSelectbox('select_repoArch', data);
    });

    function setSelectbox(attrName, data){
        $('select[name='+attrName+']').select2({
                theme: 'bootstrap4', placeholder: "{{__('Birini seç')}}", data : [{id: '', text: ''}].concat(data)
        });
        setTimeout(function(){ hideLoadingEffect(attrName); }, 500);
    }

    function resetOtherSelectBox(attrName){
        const ind = SELECT_NAME_ARRAY.indexOf(attrName);
        if(ind+1 != SELECT_NAME_ARRAY.length){
            const otherSelboxes = SELECT_NAME_ARRAY.slice(ind+1, SELECT_NAME_ARRAY.length);
            otherSelboxes.forEach(function (attr_Name) {
                $('select[name='+attr_Name+']').empty();
            });
        }
    }

    function showLoadingEffect(attrName){
        const ind = SELECT_NAME_ARRAY.indexOf(attrName);
        if(ind+1 != SELECT_NAME_ARRAY.length){
            const otherSelbox = SELECT_NAME_ARRAY[ind+1];
            $('.'+otherSelbox+'').find("div:eq(1)").show();
            $('.'+otherSelbox+'').find("div:eq(2)").hide();
        }
    }

    function hideLoadingEffect(attrName){
        $('.'+attrName+'').find("div:eq(1)").hide();
        $('.'+attrName+'').find("div:eq(2)").show();
    }

    function setRequiredSelects(attrName, bool){
        const ind = SELECT_NAME_ARRAY.indexOf(attrName);
        if(ind+1 != SELECT_NAME_ARRAY.length){
            const otherSelboxes = SELECT_NAME_ARRAY.slice(ind+1, SELECT_NAME_ARRAY.length);
            otherSelboxes.forEach(function (attr_Name) {
                if(attr_Name !== 'select_repoPackage'){
                    $('select[name='+attr_Name+']').attr('required', bool);
                }
            });
        }
    }

    function getStoragePath(mirrorName){
        let BreakException = {};
        let path = "";

        try {
            STORAGE_ARRAY.forEach(function(item) {
                if (item.text === mirrorName) {
                    path = item.path;
                    throw BreakException;
                }
            });
        } catch (e) {
             if (e !== BreakException) throw e;
        }

        return path;
    }

    function openPackagePath(row){
        const packagePath = row.querySelector('#linkPath').innerHTML;
        const checkLink = row.querySelector('#checkLink').innerHTML;
        if(checkLink === '1'){
            window.location.assign(packagePath);
        }
    }

    function copyPackagePath(row){
        const packagePath = row.querySelector('#linkPath').innerHTML;
        const txt = document.createElement('input');
            txt.value = packagePath;
            document.body.appendChild(txt);
            txt.select();
            document.execCommand('copy');
            document.body.removeChild(txt);
        showSwal('{{__("Kopyalandı")}}', 'info', 1500);
    }

    function setLinkStatus(){
        $('#packagesTable').find("td[id='checkLink']").each(function(){
            if($(this).text() === '1'){ 
                $(this).parent().find("td[id='filePath']").css("color", "blue");
            }else{//does not exist
                $(this).parent().find("td[id='filePath']").css("color", "red");
            }
        });
    }

</script>