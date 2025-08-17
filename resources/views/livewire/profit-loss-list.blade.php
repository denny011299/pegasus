<div class="table-profit-loss">
    <table class="table table-center ">
        <thead class="thead-light loss">
            <tr>
                <th>Info</th>
                <th>Jan 2023</th>
                <th>Feb 2023</th>
                <th>Mar 2023</th>
                <th>Total</th>
            </tr>
        </thead>
        <tr>
            <td class="profit space" colspan="5">
                <table class="table table-center profit">
                    <thead class="profitloss-heading">
                        <tr>
                            <th class="table-profit-head" colspan="5">Income</th>
                        </tr>
                    </thead>
                    
                    <tbody>
                        @php
                        $json = file_get_contents(public_path('../public/assets/json/profit.json'));
                        $profits = json_decode($json, true);
                    @endphp
                    @foreach ($profits as $profit)
                        <tr class="proft-head">
                            <td>{{$profit['Income']}}</td>
                            <td>{{$profit['Name']}}</td>
                            <td>{{$profit['Year1']}}</td>
                            <td>{{$profit['Year2']}}</td>
                            <td>{{$profit['Year3']}}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    
                </table>
            </td>
        </tr>
        <tr>
            <td class="loss-space" colspan="5">
                <table class="table table-center profit">
                    <thead class="profitloss-heading">
                        <tr>
                            <th class="table-profit-head" colspan="5">Expenses</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                        $json = file_get_contents(public_path('../public/assets/json/loss.json'));
                        $losses = json_decode($json, true);
                    @endphp
                    @foreach ($losses as $loss)
                        <tr>
                            <td>{{$loss['Expenses']}}</td>
                            <td>{{$loss['Name']}}</td>
                            <td>{{$loss['Year1']}}</td>
                            <td>{{$loss['Year2']}}</td>
                            <td>{{$loss['Year3']}}</td>
                        </tr>
                    @endforeach
                    </tbody>

                
                </table>
            </td>
        </tr>
    </table>
</div>
