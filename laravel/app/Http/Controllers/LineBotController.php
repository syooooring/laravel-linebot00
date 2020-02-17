<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use LINE\LINEBot;
use LINE\LINEBot\Event\MessageEvent\TextMessage;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
//LINEBotクラスのインスタンス化


class LineBotController extends Controller
{
    public function index()
    {
        return view('linebot.index');
    }

    //$requestの前に、Requestと付いているが、これは$requestがIlluminate\Http\Requestクラスのインスタンスであることを示している。
    public function parrot(Request $request)
    {
        Log::debug($request->header());
        Log::debug($request->input());
        //Log::debugを使うことでlaravel.logに情報が書き出される

        $httpClient = new CurlHTTPClient(env('LINE_ACCESS_TOKEN'));
        $lineBot = new LINEBot($httpClient, ['channelSecret' => env('LINE_CHANNEL_SECRET')]);
        //env関数を使うことで.envで設定した変数の値が返る

        $signature = $request->header('x-line-signature');
        if (!$lineBot->validateSignature($request->getContent(), $signature)) {
            abort(400, 'Invalid signature');
        }
        //署名(signature)検証
        //メッセージボディをgetContentメソッドで受け取る
        //validateSignatureメソッドで引数として受け取ったメッセージボディと署名を検証
        //if文では、!$lineBot...と、先頭に否定を意味する!が入っている。よって署名の検証結果がfalseだったら、以下を実行。
        //abort()はLaravelの関数、Webアプリとして何か異常があった時リクエスト元のブラウザやAPIに異常をレスポンスするために使う。
        //400はHTTPのステータスコード、リクエストが不正であることを意味する。
        //Invalid signatureはメッセージ
        
        
        //LINEチャネルからのPOSTリクエストのメッセージボディは、eventsとdestinationという2つの要素で構成されている。
        //LINE Messaging APIでは、メッセージ送信、友だち追加、トークルーム入室、などの出来事をイベントと呼んでいて、eventsにはその情報が入る。
        $events = $lineBot->parseEventRequest($request->getContent(), $signature);
        Log::debug($events);
        //LINEBotクラスのparseEventRequestメソッドが、リクエストからイベント情報を取り出す
        //テキストであればLINE\LINEBot\Event\MessageEvent\TextMessageクラス
        //画像であればLINE\LINEBot\Event\MessageEvent\ImageMessageクラス
        //スタンプであればLINE\LINEBot\Event\MessageEvent\StickerMessageクラス
        //種類に応じたクラスのインスタンスを返す

        foreach ($events as $event) {
            if (!($event instanceof TextMessage)) {
                Log::debug('Non text message has come');
                continue;
            }
            //複数存在する可能性のある$eventsをforeachを使って繰り返し処理。
            //$event instanceof TextMessageで、$eventがTextMessageクラスのインスタンスであるかどうかを判定。
            //もし、TextMessageでなければ、その旨をログファイルに表示。
            //そして何もせずにforeachでの次の$eventの処理に入ることができるよう、continueを行う。

            $replyToken = $event->getReplyToken();
            $replyText = $event->getText();
            $lineBot->replyText($replyToken, $replyText);
            //応答メッセージを送るには、Webhookイベントオブジェクトに含まれる応答トークン(replyToken)が必要
            //$eventはTextMessageクラスのインスタンスだが、そのgetReplyTokenメソッドで、応答トークンを取り出す。
            //LINEBotクラスのreplyTextメソッドで、テキストメッセージでの返信が行われる。
            //第一引数に応答トークン、第二引数に返信内容のテキストを渡す。
            //第二引数は、変数$replyTextを渡しているが、これは2行目でgetTextメソッドで返された内容が代入されている
            //getTextメソッドで送られてきたメッセージのテキストを取り出し、結果としてオウム返しになる。
        }
    }
}
