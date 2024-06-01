<?php

namespace App\Http\Controllers;

use App\Models\LinkShareBot;
use Illuminate\Http\Request;

class RedirectController extends Controller
{
    public function redirect(Request $request,$slug)
    {
        $shortUrl = LinkShareBot::where("slug",$slug)->first();
        if($shortUrl == null) return redirect(url("/"));

        $url = $shortUrl->url;
        $shortUrl->hits++;
        $shortUrl->update();
        header('Location: ' . $url, null, 301);

        exit;
    }
}
