<?php

namespace App\Http\Controllers;

use App\Services\Gurunavi;
use App\Services\RestaurantBubbleBuilder;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use LINE\LINEBot;
use LINE\LINEBot\Event\MessageEvent\TextMessage;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
//LINEBotクラスのインスタンス化
use LINE\LINEBot\MessageBuilder\FlexMessageBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ContainerBuilder\CarouselContainerBuilder;



class LineBotController extends Controller
{
    public function index()
    {
        return view('linebot.index');
    }

    //$requestの前に、Requestと付いているが、これは$requestがIlluminate\Http\Requestクラスのインスタンスであることを示している。
    public function restaurants(Request $request)
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

            $gurunavi = new Gurunavi();
            $gurunaviResponse = $gurunavi->searchRestaurants($event->getText());
            //Gurunaviクラス生成(インスタンス化)、$gurunaviに代入
            //getTextメソッドでユーザーからのメッセージを取り出し、これをsearchRestaurantsメソッドに渡す。
            //searchRestaurantsメソッドで、その渡されたメッセージを使いぐるなびのレストラン検索を行う。
            //検索結果の連想配列が$gurunaviResponseに代入されます。

            if (array_key_exists('error', $gurunaviResponse)) {
                $replyText = $gurunaviResponse['error'][0]['message'];
                $replyToken = $event->getReplyToken();
                $lineBot->replyText($replyToken, $replyText);
                continue;
            }
            //array_key_exists('error', $gurunaviResponseで、errorであるキーが存在するかを調べ、存在する場合はエラーメッセージを返信。

            $bubbles = [];
            //$bubblesに空の配列を代入して初期化
            foreach ($gurunaviResponse['rest'] as $restaurant) {
                $bubble = RestaurantBubbleBuilder::builder();
                $bubble->setContents($restaurant);
                $bubbles[] = $bubble;
            }
            //2行目ぐるなびAPIのレスポンスから飲食店検索結果の情報を1個ずつ取り出し、繰り返し処理を行なう
            //3行目RestaurantBubbleBuilderクラスの空のインスタンスを生成
            //4行目setContensメソッドでは、飲食店検索結果の情報をRestaurantBubbleBuilderインスタンスが持つ各種プロパティに代入
            //5行目配列$bubblesの最後に、RestaurantBubbleBuilderインスタンスを追加

            $carousel = CarouselContainerBuilder::builder();
            //CarouselContainerBuilderクラスの空のインスタンスを生成し、さきほど登場した$carouselに代入
            $carousel->setContents($bubbles);
            //setContentsメソッドでは、CarouselContainerBuilderインスタンスのプロパティcontentsに、$bubblesを代入

            $flex = FlexMessageBuilder::builder();
            //空のインスタンス生成
            //builderメソッドは、空のインスタンスを生成する、FlexMessageBuilderのstaticメソッド
            $flex->setAltText('飲食店検索結果');
            $flex->setContents($carousel);

            $lineBot->replyMessage($event->getReplyToken(), $flex);
        }
    }
}
