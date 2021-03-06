@extends('layouts.appold')
<script src="//code.jquery.com/jquery-1.11.1.js"></script>
@section('content')
<div class="container">
    <div class="alert alert-info" role="alert">
        Merhaba, <span class="text-success"> {{ Auth::user()->name }} </span>
      </div>
    <div class="row justify-content-center">
        <div class="col-md-4 mb-3">
            <div class="card">
                <div class="card-header">
                    <span style="float:left;">Bu günkü dersleriniz</span>
                    <span style="float: right; font-weight: 700; color: red;">{{ date('d.m.y') }} {{ $day ?? '' }}</span>
                </div>
                <div class="card-body">
                        @if(count($data ?? '') == 0)
                            <div>Bugün dersiniz yok.</div>
                        @else
                            <div class="list-group">
                                @foreach ($data ?? '' as $item)
                                    <a href="#" class="list-group-item list-group-item-action lesson" data-index={{ $loop->index  }} id="lesson-{{ $item['id'] }}">
                                        {{ $item['class'] }} - {{ $item['lesson'] }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                </div>
            </div>

            {{-- Raporlama --}}
            <div class="card mt-2 card-dark">
                <div class="card-header">
                    <span style="float:left;">Raporlama</span>
                </div>
                <div class="card-body">
                        <div class="list-group">
                           <button class="btn btn-block btn-success" id="raporAl">Devamsızlık Raporu Al</button>
                        </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            {{-- Yoklama için öğrenci liste/ tablosu checkboxlar da burada --}}
            <div class="card" style="display: none;" id="yoklama">
                <div class="card-header">
                    <span style="float: left;">Öğrenciler</span>
                    <span style="float: right" class="text-success" id="classHeader"></span>
                </div>
                <div class="card-body p-0">
                            <table class="table table-hover table-striped" id="list">
                                <thead>
                                    <tr>
                                        <th class="text-center">Öğrenci No</th>
                                        <th class="text-center">Yoklama</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                            <button class="btn btn-block btn-success" id="save" style="visibility: hidden">Kaydet</button>
                </div>
            </div>
            {{-- Raporlama için ... --}}
            <div class="card" style="display: none;" id="raporlama">
                <div class="card-header">
                    <span style="float: left;">Raporlama</span>
                    <span style="float: right" class="text-success" id="classHeader"></span>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="dersler">Ders Seçin</label>
                        <select class="form-control" id="dersler">
                          <option value="0">Ders Seçin</option>
                           @foreach ($lessonsAll as $item)
                                <option value="{{ $item->id }}">
                                    {{ $item->class }} - {{ $item->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <button class="btn btn-danger btn-block" id="getStats">İstatiskleri Getir</button>
                    </div>
                </div>
            </div>
            <div class="card mt-2" id="statCard" style="display: none;">
                <div class="card-body p-0">
                    <table class="table" id="statable" style="display: none;">
                        <thead>
                          <tr>
                            <th  class="text-center" scope="col">Numara</th>
                            <th  class="text-center" scope="col">Derse Katılım Oranı</th>
                            <th  class="text-center" scope="col">#</th>
                          </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>


    </div>
</div>
@endsection


{{-- <link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet"> --}}
<link href="{{asset('/css/bootstrap4-toggle.min.css' ) }}" rel="stylesheet">
<script src="{{asset('/js/bootstrap4-toggle.min.js' ) }}"></script>
<script src="{{asset('/vendor/bootstrap/js/bootstrap.min.js') }}"></script>
<script src="//code.jquery.com/ui/1.11.1/jquery-ui.js"></script>
<script src="{{asset('/vendor/sweetalert2/sweetalert2.min.js') }}"></script>
<script src="{{asset('/vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>

{{-- <script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script> --}}

<script>

    const Sure = Swal.mixin({
        title: 'Onay Verin',
        text: 'Bu sınıfın bu dersine ait yoklama kaydedilecektir, Emin misiniz?',
        icon: 'warning',
        showCancelButton: true,
        cancelButtonText: 'Vazgeç',
        confirmButtonText: 'Kaydet',
    })

    const Do = Swal.mixin({
        // toast: true,
        // position: 'top-end',
        showConfirmButton: false,
        timer: 1500,
        // dangerMode: true
        // type: 'info'
    });


    $(document).ready(function(){
        let data = {!! json_encode($data ?? '', JSON_HEX_TAG) !!}
        console.log(data)
        let currentClass = ''
        let currentLesson = ''
        let currentSchedule = ''
        let currentTeacher = "{{ Auth::user()->id }}"
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        var _token = $('input[name="_token"]').val();

        $('.lesson').on('click', function(){
            let index = $(this).attr('data-index')
            $('.lesson').removeClass('active')
            $(this).addClass('active')
            fetch(index)
        })

        $(document).on('change', '.state', function(){
            // console.log('state change')
            let number = $(this).attr('data-number')
            let state = $(this).prop('checked')
            // console.log(number, state)
        })

        $(document).on('click', '#save', function(){
            gelenler = []
            roll = []
            $("input:checkbox[name=yoklama]").each(function(){
                // gelenler.push($(this).prop('checked'))
                let num = $(this).attr('data-number')
                let state = $(this).prop('checked')
                let buff = [
                    num,
                    state
                ]
                roll.push(buff)
            });
            console.log(roll)
            // $("input:checkbox[name=yoklama]:not(:checked)").each(function(){
            //     gelmeyenler.push($(this).attr('data-number'))
            // });
            Sure.fire({text: currentClass + ' Sınıfının ' + currentLessonName + ' dersinin yoklaması kaydedilecektir. Emin misiniz?'}).then(function(isSure){
                if(isSure.value){
                    $.ajax({
                            url: "{{ route('setRollCall') }}",
                            method: 'post',
                            data:{
                                roll: roll,
                                class: currentClass,
                                lesson: currentLesson,
                                teacher: currentTeacher,
                                scheduleId: currentSchedule,
                                _token: _token
                            },
                            beforeSend: function(){
                                Swal.fire({
                                title: 'Kaydediliyor...',
                                    allowEscapeKey: false,
                                    allowOutsideClick: false,
                                    onOpen: () => {
                                    swal.showLoading();
                                    }
                                })
                            },
                            success:function(data){
                                console.log(data)
                                if(data != 'OK'){
                                    // Hata
                                    Do.fire({
                                        icon: 'error',
                                        title: 'Bir sistem hatası yaşandı, lütfen daha sonra tekrar deneyin.',
                                    })
                                }
                                else{
                                    // Başarılı
                                    Do.fire({
                                        icon: 'success',
                                        title: 'Yoklama Kaydedildi.',
                                    })
                                }

                            } // end success func
                    }) // ajax end
                } // if end
            }) // isSure end

            // console.log(gelenler, gelmeyenler)
        })

        function fetch(index){
            // console.log(data[index].students)
            let students = data[index].students
            let schedule = data[index].id
            currentClass = data[index].class
            currentLesson = data[index].lessonId
            currentLessonName = data[index].lesson
            currentSchedule = data[index].scheduleId
            currentStatus = data[index].status
            // console.log(currentStatus)
            html = ''
            let isCheck = ''
            for(var count=0; count < students.length; count++){

                if (currentStatus.length != 0) {
                    if(currentStatus[count].state == 1) isCheck = 'checked'
                    else isCheck = ''
                }
                html += '<tr data-index="' + schedule + '">';
                    html += '<td class="text-center">' + (count + 1) + '</td>';
                    html += '<td class="text-center">' + students[count].number + '</td>';
                    html += '<td class="text-center"> <input ' + isCheck + ' class="state" type="checkbox" name="yoklama" data-offstyle="danger" data-onstyle="success" data-toggle="toggle" data-width="100" data-number="' + students[count].number + '"></td>'
                html += '</tr>'
            }
            $('#raporlama').css('display', 'none');
            $('#statable').css('display', 'none');
            $('#yoklama').css('display', '');
            $('#list > tbody').html(html);
            $('#save').attr('data-id',data[index].id)
            $('#save').css('visibility', 'visible');
            $('#classHeader').html(data[index].class + ' - ' + data[index].lesson)
            $('.state').bootstrapToggle({
                on: 'Var',
                off: 'Yok',
            });
            $('.state').bootstrapToggle('on')
        }
        // $('.state').bootstrapToggle();

        $(document).on('click', '#raporAl', function(){
            $('#yoklama').css('display', 'none');
            $('#raporlama').css('display', '');
        })

        $(document).on('click', '#getStats', function(){
            let sch = $('#dersler').val()
            if(sch != 0){
                console.log(sch)
                $.ajax({
                    url: "{{ route('getStats') }}",
                    method: 'post',
                    data:{
                        sch: sch,
                        _token: _token
                    },
                    success: function(data){
                        console.log(data)
                        html = ''
                        data.map((item, index) => {
                            html += '<tr>';
                                html += '<td class="text-center">' + item.number + '</td>';
                                html += '<td><div class="progress" style="height: 19px; font-size: .9rem"><div class="progress-bar progress-bar-striped bg-danger" role="progressbar" style="width: ' + item.ortalama + '%" aria-valuenow="' + item.ortalama + '" aria-valuemin="0" aria-valuemax="100"> % ' + item.ortalama + ' </div></div></td>'
                                html += '<td class="text-center text-success">% ' + item.ortalama + '</td>';
                            html += '</tr>'
                        })
                        $('#statable > tbody').html(html);
                        $('#statable').css('display', '')
                        $('#statCard').css('display', '')
                    }
                })
            }
            else{
                Do.fire({
                    icon: 'warning',
                    title: 'Lütfen ders seçin',
                })
            }
        })

    })
</script>
