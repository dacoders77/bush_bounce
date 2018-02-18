{{ Form::open(['route' => 'history.get']) }} <!-- history.get is the name of the route -->
    {!! Form::date('start', null) !!}
    -
    {!! Form::date('end', null) !!}


{!! Form::submit('Submit') !!}
{!! Form::close() !!}



<!-- Errors handling -->
@if ($errors->any())
    <div style="color: red">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif


<!--

@if (Schema::hasTable('mytable'))
@endif

<form action="/action_page.php">
  Birthday:
  <input type="date" name="bday">
  <input type="submit">
</form>

if HTML class does not work do this:
https://laravelcollective.com/docs/5.2/html
https://laravel.com/docs/4.2/html

date picker: https://eonasdan.github.io/bootstrap-datetimepicker/
https://uxsolutions.github.io/bootstrap-datepicker/?markup=range&format=&weekStart=&startDate=&endDate=&startView=0&minViewMode=0&maxViewMode=4&todayBtn=linked&clearBtn=false&language=en&orientation=auto&multidate=&multidateSeparator=&keyboardNavigation=on&forceParse=on#sandbox

types of input: https://www.w3schools.com/Html/tryit.asp?filename=tryhtml_input_date

https://www.easylaravelbook.com/blog/creating-a-contact-form-in-laravel-5-using-the-form-request-feature/

echo Form::open(['route' => 'route.name'])
echo Form::open(['action' => 'Controller@method'])
    {{Form::label('name','Name')}}
    {{Form::text('name')}}
-->