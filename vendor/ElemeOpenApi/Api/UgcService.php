<?php

namespace ElemeOpenApi\Api;

/**
 * 订单评论服务
 */
class UgcService extends RpcService
{

    /** 获取指定订单的评论
     * @param $order_id 订单id
     * @return mixed
     */
    public function get_order_rate_by_order_id($order_id)
    {
        return $this->client->call("eleme.ugc.getOrderRateByOrderId", array("orderId" => $order_id));
    }

    /** 获取指定订单的评论
     * @param $order_ids 订单id
     * @return mixed
     */
    public function get_order_rates_by_order_ids($order_ids)
    {
        return $this->client->call("eleme.ugc.getOrderRatesByOrderIds", array("orderIds" => $order_ids));
    }

    /** 获取未回复的评论
     * @param $order_ids 订单id
     * @return mixed
     */
    public function get_unreply_order_rates_by_order_ids($order_ids)
    {
        return $this->client->call("eleme.ugc.getUnreplyOrderRatesByOrderIds", array("orderIds" => $order_ids));
    }

    /** 获取指定店铺的评论
     * @param $shop_id  餐厅id
     * @param $start_time   开始时间,只能查询最近90天的数据
     * @param $end_time   结束时间
     * @param $offset 页面偏移量
     * @param $page_size 页面大小
     * @return mixed
     */
    public function get_order_rates_by_shop_id($shop_id, $start_time, $end_time, $offset, $page_size)
    {
        return $this->client->call("eleme.ugc.getOrderRatesByShopId", array("shopId" => $shop_id, "startTime" => $start_time, "endTime" => $end_time, "offset" => $offset, "pageSize" => $page_size));
    }

    /** 获取指定店铺的评论
     * @param $shop_ids 店铺id
     * @param $start_time   开始时间,只能查询最近90天的数据
     * @param $end_time   结束时间
     * @param $offset 页面偏移量
     * @param $page_size 页面大小
     * @return mixed
     */
    public function get_order_rates_by_shop_ids($shop_ids, $start_time, $end_time, $offset, $page_size)
    {
        return $this->client->call("eleme.ugc.getOrderRatesByShopIds", array("shopIds" => $shop_ids, "startTime" => $start_time, "endTime" => $end_time, "offset" => $offset, "pageSize" => $page_size));
    }

    /** 获取未回复的评论
     * @param $shop_ids 店铺id
     * @param $start_time   开始时间,只能查询最近90天的数据
     * @param $end_time   结束时间
     * @param $offset 页面偏移量
     * @param $page_size 页面大小
     * @return mixed
     */
    public function get_unreply_order_rates_by_shop_ids($shop_ids, $start_time, $end_time, $offset, $page_size)
    {
        return $this->client->call("eleme.ugc.getUnreplyOrderRatesByShopIds", array("shopIds" => $shop_ids, "startTime" => $start_time, "endTime" => $end_time, "offset" => $offset, "pageSize" => $page_size));
    }

    /** 获取店铺的满意度评价信息
     * @param $shop_id  餐厅id
     * @param $score 满意度,取值范围为1~5，1为最不满意，5为非常满意
     * @param $start_time   开始时间,只能查询最近90天的数据
     * @param $end_time   结束时间
     * @param $offset 页面偏移量
     * @param $page_size 页面大小
     * @return mixed
     */
    public function get_order_rates_by_shop_and_rating($shop_id, $score, $start_time, $end_time, $offset, $page_size)
    {
        return $this->client->call("eleme.ugc.getOrderRatesByShopAndRating", array("shopId" => $shop_id, "score" => $score, "startTime" => $start_time, "endTime" => $end_time, "offset" => $offset, "pageSize" => $page_size));
    }

    /** 获取单个商品的评论
     * @param $item_id  商品id
     * @param $start_time   开始时间,只能查询最近90天的数据
     * @param $end_time   结束时间
     * @param $offset 页面偏移量
     * @param $page_size 页面大小
     * @return mixed
     */
    public function get_item_rates_by_item_id($item_id, $start_time, $end_time, $offset, $page_size)
    {
        return $this->client->call("eleme.ugc.getItemRatesByItemId", array("itemId" => $item_id, "startTime" => $start_time, "endTime" => $end_time, "offset" => $offset, "pageSize" => $page_size));
    }

    /** 获取多个商品的评论
     * @param $item_ids 商品id
     * @param $start_time   开始时间,只能查询最近90天的数据
     * @param $end_time   结束时间
     * @param $offset 页面偏移量
     * @param $page_size 页面大小
     * @return mixed
     */
    public function get_item_rates_by_item_ids($item_ids, $start_time, $end_time, $offset, $page_size)
    {
        return $this->client->call("eleme.ugc.getItemRatesByItemIds", array("itemIds" => $item_ids, "startTime" => $start_time, "endTime" => $end_time, "offset" => $offset, "pageSize" => $page_size));
    }

    /** 获取多个商品未回复的评论
     * @param $item_ids 店铺id
     * @param $start_time   开始时间,只能查询最近90天的数据
     * @param $end_time   结束时间
     * @param $offset 页面偏移量
     * @param $page_size 页面大小
     * @return mixed
     */
    public function get_unreply_item_rates_by_item_ids($item_ids, $start_time, $end_time, $offset, $page_size)
    {
        return $this->client->call("eleme.ugc.getUnreplyItemRatesByItemIds", array("itemIds" => $item_ids, "startTime" => $start_time, "endTime" => $end_time, "offset" => $offset, "pageSize" => $page_size));
    }

    /** 回复订单未回复的评论
     * @param $order_id 订单id
     * @param $reply 回复内容
     * @return mixed
     */
    public function reply_rate_by_order_id($order_id, $reply)
    {
        return $this->client->call("eleme.ugc.replyRateByOrderId", array("orderId" => $order_id, "reply" => $reply));
    }

    /** 批量回复订单未回复的评论
     * @param $order_ids 订单id
     * @param $reply 回复信息
     * @return mixed
     */
    public function reply_comment_by_order_ids($order_ids, $reply)
    {
        return $this->client->call("eleme.ugc.replyCommentByOrderIds", array("orderIds" => $order_ids, "reply" => $reply));
    }

    /** 回复商品回复的评论
     * @param $item_id 商品id
     * @param $reply 回复内容
     * @param $start_time   开始时间,只能查询最近90天的数据
     * @param $end_time   结束时间
     * @return mixed
     */
    public function reply_rates_by_item_id($item_id, $reply, $start_time, $end_time)
    {
        return $this->client->call("eleme.ugc.replyRatesByItemId", array("itemId" => $item_id, "reply" => $reply, "startTime" => $start_time, "endTime" => $end_time));
    }

    /** 回复多个商品评论
     * @param $item_ids 商品d
     * @param $reply 回复信息
     * @param $start_time 开始时间,只能查询最近90天的数据
     * @param $end_time 结束时间
     * @return mixed
     */
    public function reply_rates_by_item_ids($item_ids, $reply, $start_time, $end_time)
    {
        return $this->client->call("eleme.ugc.replyRatesByItemIds", array("itemIds" => $item_ids, "reply" => $reply, "startTime" => $start_time, "endTime" => $end_time));
    }

    /** 通过rate_id和shop_id 回复指定类型的评论
     * @param $rate_id 评论编号
     * @param $shop_id  餐厅id
     * @param $reply_type 评论类型
     * @param $reply 回复的内容
     * @return mixed
     */
    public function reply_rate_by_rate_id_and_shop_id($rate_id, $shop_id, $reply_type, $reply)
    {
        return $this->client->call("eleme.ugc.replyRateByRateIdAndShopId", array("rateId" => $rate_id, "shopId" => $shop_id, "replyType" => $reply_type, "reply" => $reply));
    }

    /** 通过rate_ids和shop_id 批量回复指定类型的评论
     * @param $rate_ids  评论编号
     * @param $shop_id  餐厅id
     * @param $reply_type 评论类型
     * @param $reply 回复的内容
     * @return mixed
     */
    public function reply_rate_by_rate_ids_and_shop_id($rate_ids, $shop_id, $reply_type, $reply)
    {
        return $this->client->call("eleme.ugc.replyRateByRateIdsAndShopId", array("rateIds" => $rate_ids, "shopId" => $shop_id, "replyType" => $reply_type, "reply" => $reply));
    }

    /** 根据订单_i_d赠送代金券给该订单的评价用户
     * @param $order_id  订单编号
     * @param $coupon 需要赠送的代金券信息
     * @return mixed
     */
    public function send_coupon_by_order_id($order_id, $coupon)
    {
        return $this->client->call("eleme.ugc.sendCouponByOrderId", array("orderId" => $order_id, "coupon" => $coupon));
    }

    /** 根据订单_i_d获取该订单评价用户的可赠券状态
     * @param $order_id  订单编号
     * @return mixed
     */
    public function get_order_coupon_status($order_id)
    {
        return $this->client->call("eleme.ugc.getOrderCouponStatus", array("orderId" => $order_id));
    }

    /** 根据订单_i_d集合获取该订单的已赠券信息集合
     * @param $order_ids 订单编号集合
     * @return mixed
     */
    public function get_coupons_by_order_ids($order_ids)
    {
        return $this->client->call("eleme.ugc.getCouponsByOrderIds", array("orderIds" => $order_ids));
    }

    /** 获取店铺的推荐赠送代金券信息
     * @param $shop_id 餐厅ID
     * @return mixed
     */
    public function get_recommend_coupon_by_shop_id($shop_id)
    {
        return $this->client->call("eleme.ugc.getRecommendCouponByShopId", array("shopId" => $shop_id));
    }

}