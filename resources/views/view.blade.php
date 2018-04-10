@extends('master')

@section('content')

<h1>Table output</h1>
    <br>
    <div class="container-fluid">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">All availible aliens</h3>
            </div>

            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped tabel-bordered">
                        <thead>
                            <tr>
                                <th>Country</th>
                                <th>City</th>
                                <th>Date</th>
                                <th>Number of aliens</th>
                                <th>Color of alients</th>
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

@endsection()