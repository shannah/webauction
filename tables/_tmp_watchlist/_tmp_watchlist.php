<?php
class tables__tmp_watchlist {
    function __sql__() {
        $reverseAuction = getConf('reverse_auction');

		if ( $reverseAuction ){
			$highbidamount = 'min(bid_amount)';
			$highbidderq = 'bid_amount<b.bid_amount';
		} else {
			$highbidamount = 'max(bid_amount)';
			$highbidderq = 'bid_amount>b.bid_amount';
		}

		$sql = "select p.product_id, p.product_name, concat('\$',format(high_bid,2)) as high_bid, high_bid as high_bid_float, high_bidder from products p inner join
					(
						select product_id, $highbidamount as high_bid from bids group by product_id
					) as hb
					on p.product_id=hb.product_id
					inner join
					(
						select product_id, username as high_bidder from bids b where not exists (select * from bids where product_id=b.product_id and $highbidderq)
					) as hbr
					on p.product_id=hbr.product_id
				where '".getUsername()."' in
					(select username from bids where product_id=p.product_id)

				";
        return $sql;
    }
}
