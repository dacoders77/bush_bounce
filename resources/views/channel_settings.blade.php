@if (Schema::hasTable('assets'))

{{ Form::open(['route' => 'pricechannel']) }} <!-- pricechannel is the name of the route. controller: priceChannel_controller@index -->
{!! Form::label('xx', 'Price chan. period(bar) / stop loss chan. shift(%): ') !!}
<br>
{!! Form::number('channel_period',
    DB::table('settings')
        ->where('id', '1')
        ->value('default_price_channel_period'), array('min'=>1, 'max'=>100))  !!}
/
{{ Form::number('stop_loss_shift',
    DB::table('settings')
    ->where('id', '1')
    ->value('default_stop_loss_shift'), array('min'=>1, 'max'=>100))}}

{!! Form::submit('Submit') !!}
{!! Form::close() !!}
@endif

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

echo Form::open(['route' => 'route.name'])
echo Form::open(['action' => 'Controller@method'])
{{Form::label('name','Name')}}
{{Form::text('name')}}
        -->