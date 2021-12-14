<?php
/**
 * @package Cart
 * @author tehcvillage <support@techvill.org>
 * @contributor Sakawat Hossain Rony <[sakawat.techvill@gmail.com]>
 * @created 24-11-2021
 */
namespace App\Cart;
use Validator;

class Cart
{
    /**
     * Added item in cart
     *
     * @param array $data
     * @return bool
     */
    public function add($data = [], $index = null)
    {
        $validator = $this->validate($data);
        if ($validator->fails()) {
            return false;
        }
        $cart = $this->getCartData();
        if (!$cart) {
            $cart[] = [
                    "id" => $data['id'],
                    "item_code" => $data['item_code'],
                    "vendor_id" => $data['vendor_id'],
                    "shop_id" => $data['shop_id'],
                    "name" => $data['name'],
                    "quantity" => $data['quantity'],
                    "price" => $data['price'],
                    "actual_price" => $data['actual_price'],
                    "photo" => $data['photo'],
                    "discount_amount" => $data['discount_amount'],
                    "discount_type" => $data['discount_type'],
                    "option_id" => $data['option_id'],
                    "option_name" => $data['option_name'],
                    "option" => $data['option'],
                 ];
            $this->save($cart);
            $this->destroyCoupon();
            return true;
        } elseif (isset($cart[$index]['id']) && $cart[$index]['id'] == $data['id']) {
            $cart[$index]['quantity'] = $cart[$index]['quantity'] + $data['quantity'];
            $this->save($cart);
            return true;
        } else {
            $cart[] = [
                "id" => $data['id'],
                "item_code" => $data['item_code'],
                "vendor_id" => $data['vendor_id'],
                "shop_id" => $data['shop_id'],
                "name" => $data['name'],
                "quantity" => $data['quantity'],
                "price" => $data['price'],
                "actual_price" => $data['actual_price'],
                "photo" => $data['photo'],
                "discount_amount" => $data['discount_amount'],
                "discount_type" => $data['discount_type'],
                "option_id" => $data['option_id'],
                "option_name" => $data['option_name'],
                "option" => $data['option'],
            ];
            $this->save($cart);
            $this->destroyCoupon();
            return true;
        }
    }

    /**
     * cart item decrement
     *
     * @param $id
     * @return bool|void
     */
    public function reduceQuantity($index)
    {
        $cart = $this->getCartData();
        if (isset($cart[$index])) {
            if ($cart[$index]['quantity'] > 1) {
                $cart[$index]['quantity']--;
                $this->save($cart);
                return true;
            } else {
                $this->destroy($index);
            }
        }
    }

    /**
     * return all cart item
     *
     * @return mixed
     */
    public function getCartData()
    {
        return session()->get('cart');
    }

    /**
     * return coupon
     *
     * @return mixed
     */
    public function getCouponData()
    {
        return session()->get('coupon');
    }

    /**
     * cart item in collection
     *
     * @return CartCollection
     */
    public function cartCollection()
    {
        return new CartCollection(session()->get('cart'));
    }

    /**
     * total item of cart
     *
     * @return int
     */
    public function totalItem()
    {
        $cart = $this->cartCollection();
        return $cart->count();

    }

    /**
     * total quantity of cart
     *
     * @return int|mixed
     */
    public function totalQuantity()
    {
        $cart = $this->cartCollection();
        if ($cart->isEmpty()) return 0;

        $count = $cart->sum(function ($cart) {
            return $cart['quantity'];
        });
        return $count;
    }

    /**
     * total price of cart
     *
     * @return int|mixed
     */
    public function totalPrice()
    {
        $cart = $this->cartCollection();
        if ($cart->isEmpty()) return 0;

        $count = $cart->sum(function ($cart) {
            return $cart['price'] * $cart['quantity'];
        });
        return $count;
    }


    /**
     * validate cart item
     *
     * @param array $item
     * @return mixed
     */
    public function validate($item = [])
    {
        $validator = Validator::make($item, [
            'id' => 'required',
            'item_code' => 'required',
            'price' => 'required|numeric',
            'quantity' => 'required|numeric|min:1',
            'name' => 'required',
            'photo' => 'required'
        ]);

        return $validator;
    }

    /**
     * removes an item on cart by item ID
     *
     * @param $id
     * @return bool
     */
    public function destroy($index = null, $action = 'single')
    {
        if ($action == 'single') {
            $cart = $this->getCartData();
            unset($cart[$index]);
            $this->save($cart);
        } else {
            session()->forget('cart');
            $this->destroyCoupon();
        }
        return true;
    }

    /**
     * removes coupon
     *
     * @param $id
     * @return bool
     */
    public function destroyCoupon()
    {
        if (session()->has('coupon')) {
            session()->forget('coupon');
        }
    }

    /**
     * save the cart
     *
     * @param $cart
     * @return bool
     */
    protected function save($cart)
    {
        session()->put('cart', $cart);
    }

    /**
     * coupon save
     *
     * @param $data
     */
    public function couponSave($data)
    {
        session()->put('coupon', $data);
    }

    /**
     * item selected
     *
     * @param $data
     */
    public function selectedStore($data = [])
    {
        $this->selectedDestroy();
        session()->put('selected', $data);
        $this->destroyCoupon();
    }

    /**
     * get selected item
     *
     * @param $data
     */
    public function getSelected()
    {
        return session()->get('selected');
    }

    /**
     * item selected destroy
     *
     * @param $data
     */
    public function selectedDestroy()
    {
        if (session()->has('selected')) {
            session()->forget('selected');
        }
    }
}
