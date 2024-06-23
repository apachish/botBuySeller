<!DOCTYPE html>
<html lang="fa">
<head>
    <title>{{data_get($customer,"fullName")}}</title>
    <meta charset="UTF-8">
    <style>
        @font-face {
            font-family: 'Vazirmatn';
            src: url('{{ storage_path('fonts/Vazirmatn-Regular.ttf') }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        @font-face {
            font-family: 'Vazirmatn';
            src: url('{{ storage_path('fonts/Vazirmatn-Bold.ttf') }}') format('truetype');
            font-weight: bold;
            font-style: normal;
        }
        body {
            font-family: 'Vazirmatn', sans-serif;
            direction: rtl;
            text-align: right;
            font-size:14px;
            padding:14px;
        }
        * {
            box-sizing:border-box;
            padding:0;
            margin:0;
            outline: 0;
        }
        article {
            width:100%;
            max-width:1000px;
            margin:0 auto;
            height:1000px;
            position:relative;
        }
        ul {
            display:flex;
            top:0px;
            z-index:10;
            padding-bottom:14px;
        }
        li {
            list-style:none;
            direction: rtl;
            flex:1;
        }
        li:last-child {
            border-right:1px solid #DDD;
        }
        button {
            width:100%;
            border: 1px solid #DDD;
            border-right:0;
            border-top:0;
            padding: 10px;
            background:#FFF;
            font-size:14px;
            font-weight:bold;
            height:60px;
            color:#999;
            font-family: 'Yekan';
        }
        li.active button {
            background:#F5F5F5;
            color:#000;
        }
        table { border-collapse:collapse; table-layout:fixed; width:100%; }
        th { background:#F5F5F5; display:none; }
        td, th {
            height:53px
        }
        td,th { border:1px solid #DDD; padding:10px; empty-cells:show; }
        td,th {
            text-align:left;
        }
        td+td, th+th {
            text-align:center;
            display:none;
        }
        td.default {
            display:table-cell;
        }
        .bg-purple {
            border-top:3px solid #A32362;
        }
        .bg-blue {
            border-top:3px solid #0097CF;
        }
        .sep {
            background:#F5F5F5;
            font-weight:bold;
        }
        .txt-l { font-size:28px; font-weight:bold; }
        .txt-top { position:relative; top:-9px; left:-2px; }
        .tick { font-size:18px; color:#2CA01C; }
        .hide {
            border:0;
            background:none;
        }
    </style>
</head>
<body dir="rtl">
<article>
    <ul>
        <li class="bg-purple">
            <button>{{data_get($customer,"fullName")}} معامله گر :</button>
        </li>
        <li class="bg-blue">
            <button>{{toJalali(now(),"Y/m/d H:i:s")}} تاریخ :</button>
        </li>
        <li class="bg-blue active">
            <button>{{$date_p}} لیست معاملات گذشته به تاریخ :</button>
        </li>
        <li class="bg-blue">
            <button></button>
        </li>
    </ul>

    <table>
        <thead>
        <tr>
            <th class="bg-purple">شماره حواله</th>
            <th class="bg-blue">نوع</th>
            <th class="bg-blue">طرف معامله</th>
            <th class="bg-blue default">موعد معامله</th>
            <th class="bg-purple" >حجم</th>
            <th class="bg-blue default">حالت</th>
            <th class="bg-blue">توضیحات</th>
            <th class="bg-purple">فی(تومان)</th>
            <th class="bg-blue">تاریخ</th>
        </tr>
        </thead>
        <tbody>
        @foreach($request_transfer as $i=> $item)
            <tr>

                <td>{{data_get($item,'requestTransfer.id')}}</td>
                 <td style="color: {{$color}}">{{$type . getTypeTitleOrder($type)}}</td>
                <td>{{$name_owner}}</td>
                <td>{{toJalali(data_get($item,"date"),"Y/m/d")}}</td>
                <td>{{data_get($item,"requestTransfer.number")}}</td>
                <td>{{getTypeTransfer(data_get($item,"type"))}}</td>
                <td>{{data_get($item,"description")}}</td>
                <td style="color:{{$color}}">{{number_format(data_get($item,"price"))}}</td>
                <td>{{toJalali(data_get($item,'requestTransfer.created_at'),"Y/m/d H:i:s")}}</td>
            </tr>
        @endforeach
        {{--        <tr>--}}
        {{--            <td>تعرفه ماهانه</td>--}}
        {{--            <td><span class="txt-l">3000</span><span class="txt-top">تومان</span></td>--}}
        {{--            <td><span class="txt-l">5000</span><span class="txt-top">تومان</span></td>--}}
        {{--            <td class="default"><span class="txt-l">6000</span><span class="txt-top">تومان</span></td>--}}
        {{--            <td><span class="txt-l">8000</span><span class="txt-top">تومان</span></td>--}}
        {{--        </tr>--}}
        {{--        <tr>--}}
        {{--            <td colspan="5" class="sep">به سادگی شروع کنید</td>--}}
        {{--        </tr>--}}

        </tbody>
    </table>
</article>
</body>
</html>
