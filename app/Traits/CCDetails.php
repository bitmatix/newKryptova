<?php

namespace App\Traits;

use Illuminate\Http\Request;

trait CCDetails
{
	// ================================================
	/* method : ccExpiryMonthTwoDigit
	* @param  : 
	* @description : month 2 digit
	*/// ==============================================
	public function ccExpiryMonthTwoDigit($ccExpiryMonth)
	{
		$ccExpiryMonth = trim($ccExpiryMonth);
        
        if(strlen($ccExpiryMonth) == '1') {
            $ccExpiryMonth = '0'.$ccExpiryMonth;
        }

        return $ccExpiryMonth;
	}

	// ================================================
	/* method : ccExpiryYearFourDigit
	* @param  : 
	* @description : year 4 digit
	*/// ==============================================
	public function ccExpiryYearFourDigit($ccExpiryYear)
	{
		$ccExpiryYear = trim($ccExpiryYear);
		
        if(strlen($ccExpiryYear) == '2') {
            $ccExpiryYear = '20'.$ccExpiryYear;
        }

        return $ccExpiryYear;
	}

	// ================================================
	/* method : getCardType
	* @param  : 
	* @description : return card type
	*/// ==============================================
	public function getCardTypeBack($card_no, $extra_check = false)
    {
        if (empty($card_no)) {
            return false;
        }

        $cards = array(
            "visa" => "(4\d{12}(?:\d{3})?)",
            "mastercard" => "(5[1-5]\d{14})",
            "amex" => "(3[47]\d{13})",
            "jcb" => "(35[2-8][89]\d\d\d{10})",
            "solo" => "((?:6334|6767)\d{12}(?:\d\d)?\d?)",
            "maestro" => "((?:5020|5038|6304|6579|6761)\d{12}(?:\d\d)?)",
            "discover" => "/^65[4-9][0-9]{13}|64[4-9][0-9]{13}|6011[0-9]{12}|(622(?:12[6-9]|1[3-9][0-9]|[2-8][0-9][0-9]|9[01][0-9]|92[0-5])[0-9]{10})$/",
            "switch" => "/^(4903|4905|4911|4936|6333|6759)[0-9]{12}|(4903|4905|4911|4936|6333|6759)[0-9]{14}|(4903|4905|4911|4936|6333|6759)[0-9]{15}|564182[0-9]{10}|564182[0-9]{12}|564182[0-9]{13}|633110[0-9]{10}|633110[0-9]{12}|633110[0-9]{13}$/",
        );

        $names = array("Visa", "American Express", "JCB", "Maestro", "Solo", "Mastercard", "Switch","Discover");
        $matches = array();
        $pattern = "#^(?:".implode("|", $cards).")$#";
        $result = preg_match($pattern, str_replace(" ", "", $card_no), $matches);
        if($extra_check && $result > 0){
            $result = (validatecard($card_no))?1:0;
        }
        $card = ($result>0)?$names[sizeof($matches)-2]:false;

        switch ($card):
            case 'Visa':
                return '2';
                break;
            case 'American Express':
                return '1';
                break;
            case 'Maestro':
                return '6';
                break;
            case 'Mastercard':
                return '3';
                break;
            case 'Discover':
                return '4';
                break;
            case 'JCB':
                return '5';
                break;
            case 'Switch':
                return '7';
                break;
            case 'Solo':
                return '8';
                break;
            default :
                return false;
                break;
        endswitch;
    }

    public function getCardType($card_no)
    {
        if (empty($card_no)) {
            return false;
        }
        $cardtype = array(
            "visa" => "/^4[0-9]{12}(?:[0-9]{3})?$/",
            "mastercard" => "/^5[1-5][0-9]{14}$/",
            "amex" => "/^3[47]\d{13,14}$/",
            "jcb" => "/^(?:2131|1800|35\d{3})\d{11}$/",
            "solo" => "/^(6334|6767)[0-9]{12}|(6334|6767)[0-9]{14}|(6334|6767)[0-9]{15}$/",
            "maestro" => "/^(5018|5020|5038|6304|6759|6761|6763)[0-9]{8,15}$/",
            "discover" => "/^65[4-9][0-9]{13}|64[4-9][0-9]{13}|6011[0-9]{12}|(622(?:12[6-9]|1[3-9][0-9]|[2-8][0-9][0-9]|9[01][0-9]|92[0-5])[0-9]{10})$/",
            "switch" => "/^(4903|4905|4911|4936|6333|6759)[0-9]{12}|(4903|4905|4911|4936|6333|6759)[0-9]{14}|(4903|4905|4911|4936|6333|6759)[0-9]{15}|564182[0-9]{10}|564182[0-9]{12}|564182[0-9]{13}|633110[0-9]{10}|633110[0-9]{12}|633110[0-9]{13}$/",
            );

        if (preg_match($cardtype['visa'], $card_no)) {
            return '2';
        } else if (preg_match($cardtype['mastercard'], $card_no)) {
            return '3';
        } else if (preg_match($cardtype['amex'], $card_no)) {
            return '1';
        } else if (preg_match($cardtype['discover'], $card_no)) {
            return '4';
        } else if (preg_match($cardtype['jcb'], $card_no)) {
            return '5';
        } else if (preg_match($cardtype['maestro'], $card_no)) {
            return '6';
        } else if (preg_match($cardtype['switch'], $card_no)) {
            return '7';
        } else if (preg_match($cardtype['solo'], $card_no)) {
            return '8';
        } else {
            return false;
        }
    }
}