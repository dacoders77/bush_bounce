@if (Schema::hasTable('assets'))

{{ Form::open(['route' => 'pricechannel']) }} <!-- pricechannel is the name of the route. controller: priceChannel_controller@index -->

{!! Form::label('xx', 'Price channel period: ') !!}

<br>

{!! Form::number('channel_period', DB::table('assets')->where('asset_name', 'btcusd')->value('price_channel_default_value'), array('min'=>1, 'max'=>100))  !!}

-
{{ Form::number('channel_period_start', DB::table('assets')->where('asset_name', 'btcusd')->value('price_channel_start'), array('min'=>1, 'max'=>100))}}
-
{{ Form::number('channel_period_end', DB::table('assets')->where('asset_name', 'btcusd')->value('price_channel_end'), array('min'=>1, 'max'=>100))}}


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