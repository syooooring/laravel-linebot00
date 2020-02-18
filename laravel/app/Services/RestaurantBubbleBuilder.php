<?php

namespace App\Services;

use LINE\LINEBot\MessageBuilder\Flex\ContainerBuilder;

class RestaurantBubbleBuilder implements ContainerBuilder
{
    private $imageUrl;
    private $name;
    private $closestStation;
    private $minutesByFoot;
    private $category;
    private $budget;
    private $latitude;
    private $longitude;
    private $phoneNumber;
    private $restaurantUrl;
}

/*
RestaurantBubbleBuilderクラスでは複数のメソッド間で、ぐるなびAPIの飲食店検索結果を使いまわす。
具体的には、のちほど定義するメソッドのうち、
setContensメソッドでは、ぐるなびAPIの検索結果をプロパティに代入
builderメソッドでは、それらプロパティをもとにバブルコンテナの連想配列を返す
そのためのプロパティ宣言。
これらプロパティはRestaurantBubbleBuilderクラス以外のクラスなどの「外側」から参照させる必要がないので、
参照不可となるようにプロパティ名の前にprivateを付けています。 
*/