<?php

namespace ElemeOpenApi\Api;

/**
 * 店铺服务
 */
class ShopService extends RpcService
{

    /** 查询店铺信息
     * @param $shop_id 店铺Id
     * @return mixed
     */
    public function get_shop($shop_id)
    {
        return $this->client->call("eleme.shop.getShop", array("shopId" => $shop_id));
    }

    /** 更新店铺基本信息
     * @param $shop_id 店铺Id
     * @param $properties 店铺属性
     * @return mixed
     */
    public function update_shop($shop_id, $properties)
    {
        return $this->client->call("eleme.shop.updateShop", array("shopId" => $shop_id, "properties" => $properties));
    }

    /** 批量获取店铺简要
     * @param $shop_ids 店铺Id的列表
     * @return mixed
     */
    public function mget_shop_status($shop_ids)
    {
        return $this->client->call("eleme.shop.mgetShopStatus", array("shopIds" => $shop_ids));
    }

    /** 设置送达时间
     * @param $shop_id 店铺Id
     * @param $delivery_basic_mins 配送基准时间(单位分钟)
     * @param $delivery_adjust_mins 配送调整时间(单位分钟)
     * @return mixed
     */
    public function set_delivery_time($shop_id, $delivery_basic_mins, $delivery_adjust_mins)
    {
        return $this->client->call("eleme.shop.setDeliveryTime", array("shopId" => $shop_id, "deliveryBasicMins" => $delivery_basic_mins, "deliveryAdjustMins" => $delivery_adjust_mins));
    }

    /** 设置是否支持在线退单
     * @param $shop_id 店铺Id
     * @param $enable 是否支持
     * @return mixed
     */
    public function set_online_refund($shop_id, $enable)
    {
        return $this->client->call("eleme.shop.setOnlineRefund", array("shopId" => $shop_id, "enable" => $enable));
    }

    /** 设置是否支持预定单及预定天数
     * @param $shop_id 店铺id
     * @param $enabled 是否支持预订
     * @param $max_booking_days 最大预定天数
     * @return mixed
     */
    public function set_booking_status($shop_id, $enabled, $max_booking_days)
    {
        return $this->client->call("eleme.shop.setBookingStatus", array("shopId" => $shop_id, "enabled" => $enabled, "maxBookingDays" => $max_booking_days));
    }

    /** 批量通过店铺_id获取_oid
     * @param $shop_ids 店铺Id的列表
     * @return mixed
     */
    public function get_oid_by_shop_ids($shop_ids)
    {
        return $this->client->call("eleme.shop.getOidByShopIds", array("shopIds" => $shop_ids));
    }

}