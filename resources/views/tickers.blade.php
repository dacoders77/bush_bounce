Dataset configuration page<br>
<i>Requesting significant amount of assets as well as history load period can result in protracted request processing</i><br>
<br>

{{ Form::open(['route' => 'tickers_record_todb.post']) }} <!-- history.get is the name of the route -->

History load period. From: {!! Form::date('start', date_format(date_create(DB::table('assets')->where('id', 1)->value('load_history_start')), 'Y-m-d')) !!}
To: {!! Form::date('end', date_format(date_create(DB::table('assets')->where('id', 1)->value('load_history_end')), 'Y-m-d')) !!} <br>
Time frame:<br>
{{ Form::radio('radio1', '1m', false)}}
{{ Form::label('1m')}}
{{ Form::radio('radio1', '5m', false)}}
{{ Form::label('5m')}}
{{ Form::radio('radio1', '15m', false)}}
{{ Form::label('15m')}}
{{ Form::radio('radio1', '30m', false)}}
{{ Form::label('30m')}}
{{ Form::radio('radio1', '1h', true)}}
{{ Form::label('1h')}}
{{ Form::radio('radio1', '1d', false)}}
{{ Form::label('1d')}}


<br><br>
Select/Show on startup/Asset<br>
@foreach($tickers as $ticker)

    {{ Form::checkbox($ticker,'true',false) }}
    {{ Form::radio('radio', $ticker, false) }}
    {{ Form::label($ticker)}}
    <br>

@endforeach


{!! Form::submit('Submit') !!}
{{ Form::reset('Clear') }}

{!! Form::close() !!}








