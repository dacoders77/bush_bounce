<table>
    <tr>
        <td>Initial capital: </td>
        <td>10000.00</td>
    </tr>
    <tr>
        <td>Ending capital: </td>
        <td id="ending_capital">-</td>
    </tr>
    <tr>
        <td>Net profit: </td>
        <td id="net_profit">-</td>
    </tr>
    <tr>
        <td>Net profit %: </td>
        <td id="net_profit_prc">-</td>
    </tr>
    <tr>
        <td>Drawdown: </td>
        <td id="drawdown">-</td>
    </tr>
    <tr>
        <td>Drawdown %: </td>
        <td id="drawdown_prc">-</td>
    </tr>
    <tr>
        <td>Trades quantity: </td>
        <td id="trades_quan"></td>
    </tr>
    <tr>
        <td>Long trades: </td>
        <td id="profit_trades">-</td>
    </tr>
    <tr>
        <td>Short trades: </td>
        <td id="loss_trades">-</td>
    </tr>
</table>

<style>
    table {
        font-family: arial, sans-serif;
        border-collapse: collapse;
        width: 100%;
    }

    td, th {
        border: 1px solid #dddddd;
        text-align: left;
        padding: 3px;
    }

    tr:nth-child(even) {
        background-color: #dddddd;
    }
</style>

<!--  DB::table('assets')->where('id', 1)->value('ending_capital') !!} -->