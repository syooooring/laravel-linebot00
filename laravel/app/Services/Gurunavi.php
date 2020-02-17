<?php

namespace App\Services;

use GuzzleHttp\Client;

class Gurunavi
{

  private const RESTAURANTS_SEARCH_API_URL = 'https://api.gnavi.co.jp/RestSearchAPI/v3/';


    public function searchRestaurants(string $word): array
    {
      $client = new Client();
      //GuzzleのClientクラス(インスタンス化)生成
      //$clientに代入
        $response = $client
            ->get(self::RESTAURANTS_SEARCH_API_URL, [
                'query' => [
                    'keyid' => env('GURUNAVI_ACCESS_KEY'),
                    'freeword' => str_replace(' ', ',', $word),
                ],
                'http_errors' => false,
            ]);
        return json_decode($response->getBody()->getContents(), true);
        //$clientでgetメソッドを使い、指定したURLに対してGETリクエストを行う、そのレスポンスが返る。
        //第一引数にリクエスト先のURLを指定
        //第二引数に、オプションとなる情報を連想配列で渡す。
        //'query'をキーとする連想配列でリクエストパラメータを指定
        //'keyid'で、env関数を使い、.envファイルのGURUNAVI_ACCESS_KEYの値をセット。
        //'freeword'については、searchRestaurantsメソッドに渡された$wordをstr_replace関数で加工した上でセット。
        //URLとオプションを指定した上でgetメソッドを使い、$responseへぐるなびAPIのレスポンスを代入。
    }
}