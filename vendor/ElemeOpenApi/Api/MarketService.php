<?php

namespace ElemeOpenApi\Api;

/**
 * 服务市场服务
 */
class MarketService extends RpcService
{

    /** 同步某一段时间内的服务市场消息
     * @param $start 开始时间
     * @param $end 结束时间
     * @param $offset 消息偏移量
     * @param $limit 查询消息数
     * @return mixed
     */
    public function sync_market_messages($start, $end, $offset, $limit)
    {
        return $this->client->call("eleme.market.syncMarketMessages", array("start" => $start, "end" => $end, "offset" => $offset, "limit" => $limit));
    }

}