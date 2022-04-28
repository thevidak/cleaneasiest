<div class="bg-white rounded shadow-sm p-4 py-4 d-flex flex-column">
    <div class="row form-group align-items-baseline">
        <div class="col pe-md-0">
            <span>Servisi:</span>
        </div>
    </div>
    
    <table class="table">
        <thead>
           <tr>
               <th class="text-start"> 
                   <div>Ime</div>
               </th>
               <th class="text-start"> 
                   <div>Tezina</div>
               </th>
               <th class="text-start"> 
                   <div>Napomena</div>
               </th>
           </tr> 
        </thead>
        <tbody>
        @foreach ($services as $item)
            <tr>
                <td class="text-start  text-truncate" colspan="1">
                    <div>{{ $item['name'] }}</div>
                </td>
                <td class="text-start  text-truncate" colspan="1">
                    <div>{{ $item['weight'] }}</div>
                </td>
                <td class="text-start  text-truncate" colspan="1">
                    <div>{{ $item['note'] }}</div>
                </td>
            </tr>
        @endforeach
        </tbody>

</table>
</div>