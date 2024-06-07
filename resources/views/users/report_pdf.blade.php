<!DOCTYPE html>
<html>
<head>
    <title>{{data_get($customer,"fullName")}}</title>
</head>
<body>
<table dir="rtl" style="text-align:right;width: 100%; border: thick;border-color: #000000">
    <thead>
    <tr style="background-color: aqua">
        <p>{{data_get($customer,"fullName")}} معامله گر :</p>
        <p>{{toJalali(now(),"Y/m/d H:i:s")}} تاریخ :</p>
        <p>{{$date_p}} لیست معاملات گذشته به تاریخ :</p>
    </tr>
    <tr>
        <th>ردیف</th>
        <th>نوع</th>
        <th>طرف معامله</th>
        <th>موعد معامله</th>
        <th>حجم</th>
        <th>حالت</th>
        <th>توضیحات</th>
        <th>فی(تومان)</th>
        <th>تاریخ</th>
    </tr>
    </thead>
    <tbody></tbody>
</table>
@foreach(array_filter($request_transfer) as $i=> $item)
    <tr>
        @php
            $color = "dodgerblue";
            $type = getTypeOrder(data_get($item,"transfer.type"));
            if($type == "sell")
                $color = "#ef4444";
        @endphp
        <td>{{$i+1}}</td>
        <td style="color: {{$color}}">{{getTypeTitleOrder(data_get($item,"transfer.type"))}}</td>
        <td>{{data_get($item,"transfer.user.fullName")}}</td>
        <td>{{toJalali(data_get($item,"transfer.date"),"Y/m/d")}}</td>
        <td>{{data_get($item,"number")}}</td>
        <td>{{getTypeTransfer(data_get($item,"transfer.type"))}}</td>
        <td>{{data_get($item,"transfer.description")}}</td>
        <td style="color:{{$color}}">{{number_format(data_get($item,"transfer.price"))}}</td>
        <td>{{toJalali($item->created_at,"Y/m/d H:i:s")}}</td>
    </tr>
    @endforeach
    </div>
</body>
</html>
