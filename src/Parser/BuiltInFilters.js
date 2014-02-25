/**
* IMPORTANT NOTE: those filters are only meant to catch bad input and honest mistakes. They don't
*                 match their PHP equivalent exactly and may let unwanted values through. Their
*                 result should always be checked by PHP filters
*
* @const
*/
var BuiltInFilters =
{
	/**
	* @param  {*} attrValue
	* @return {*}
	*/
	filterAlnum: function(attrValue)
	{
		return /^[0-9A-Za-z]+$/.test(attrValue) ? attrValue : false;
	},

	/**
	* @param  {*} attrValue
	* @return {*}
	*/
	filterColor: function(attrValue)
	{
		return /^(?:#[0-9a-f]{3,6}|rgb\(\d{1,3}, *\d{1,3}, *\d{1,3}\)|[a-z]+)$/i.test(attrValue) ? attrValue : false;
	},

	/**
	* @param  {*} attrValue
	* @return {*}
	*/
	filterEmail: function(attrValue)
	{
		return /^[-\w.+]+@[-\w.]+$/.test(attrValue) ? attrValue : false;
	},

	/**
	* @param  {*} attrValue
	* @return {*}
	*/
	filterFloat: function(attrValue)
	{
		return /^(?:0|-?[1-9]\d*)(?:\.\d+)?(?:e[1-9]\d*)?$/i.test(attrValue) ? attrValue : false;
	},

	/**
	* @param  {*}        attrValue Original value
	* @param  {!Object}  map       Hash map
	* @param  {!boolean} strict    Whether this map is strict (values with no match are invalid)
	* @return {*}                  Filtered value, or FALSE if invalid
	*/
	filterHashmap: function(attrValue, map, strict)
	{
		if (attrValue in map)
		{
			return map[attrValue];
		}

		return (strict) ? false : attrValue;
	},

	/**
	* @param  {*} attrValue
	* @return {*}
	*/
	filterIdentifier: function(attrValue)
	{
		return /^[-\w]+$/.test(attrValue) ? attrValue : false;
	},

	/**
	* @param  {*} attrValue
	* @return {*}
	*/
	filterInt: function(attrValue)
	{
		return /^(?:0|-?[1-9]\d*)$/.test(attrValue) ? attrValue : false;
	},

	/**
	* @param  {*} attrValue
	* @return {*}
	*/
	filterIp: function(attrValue)
	{
		if (/^[\d.]+$/.test(attrValue))
		{
			return BuiltInFilters.filterIpv4(attrValue);
		}

		if (/^[\da-f:]+$/i.test(attrValue))
		{
			return BuiltInFilters.filterIpv6(attrValue);
		}

		return false;
	},

	/**
	* @param  {*} attrValue
	* @return {*}
	*/
	filterIpport: function(attrValue)
	{
		var m, ip;

		if (m = /^\[([\da-f:]+)(\]:[1-9]\d*)$/i.exec(attrValue))
		{
			ip = BuiltInFilters.filterIpv6(m[1]);

			if (ip === false)
			{
				return false;
			}

			return '[' + ip + m[2];
		}

		if (m = /^([\d.]+)(:[1-9]\d*)$/.exec(attrValue))
		{
			ip = BuiltInFilters.filterIpv4(m[1]);

			if (ip === false)
			{
				return false;
			}

			return ip + m[2];
		}

		return false;
	},

	/**
	* @param  {*} attrValue
	* @return {*}
	*/
	filterIpv4: function(attrValue)
	{
		if (/^\d+\.\d+\.\d+\.\d+$/.test(attrValue))
		{
			return false;
		}

		var i = 4, p = attrValue.split('.');
		while (--i >= 0)
		{
			// NOTE: ext/filter doesn't support octal notation
			if (p[i].charAt(0) === '0' || p[i] > 255)
			{
				return false;
			}
		}

		return true;
	},

	/**
	* @param  {*} attrValue
	* @return {*}
	*/
	filterIpv6: function(attrValue)
	{
		return /^(\d*:){2,7}\d+(?:\.\d+\.\d+\.\d+)?$/.test(attrValue) ? attrValue : false;
	},

	/**
	* @param  {*} attrValue
	* @param  {!Array.<!Array>}  map
	* @return {*}
	*/
	filterMap: function(attrValue, map)
	{
		var i = -1, cnt = map.length;
		while (++i < cnt)
		{
			if (map[i][0].test(attrValue))
			{
				return map[i][1];
			}
		}

		return attrValue;
	},

	/**
	* @param  {*} attrValue
	* @return {*}
	*/
	filterNumber: function(attrValue)
	{
		return /^\d+$/.test(attrValue) ? attrValue : false;
	},

	/**
	* @param  {*}       attrValue
	* @param  {!number} min
	* @param  {!number} max
	* @param  {Logger}  logger
	* @return {!number|boolean}
	*/
	filterRange: function(attrValue, min, max, logger)
	{
		if (!/^(?:0|-?[1-9]\d*)$/.test(attrValue))
		{
			return false;
		}

		attrValue = parseInt(attrValue, 10);

		if (attrValue < min)
		{
			if (logger)
			{
				logger.warn(
					'Value outside of range, adjusted up to min value',
					{
						'attrValue' : attrValue,
						'min'       : min,
						'max'       : max
					}
				);
			}

			return min;
		}

		if (attrValue > max)
		{
			if (logger)
			{
				logger.warn(
					'Value outside of range, adjusted down to max value',
					{
						'attrValue' : attrValue,
						'min'       : min,
						'max'       : max
					}
				);
			}

			return max;
		}

		return attrValue;
	},

	/**
	* @param  {*} attrValue
	* @param  {!RegExp} regexp
	* @return {*}
	*/
	filterRegexp: function(attrValue, regexp)
	{
		return regexp.test(attrValue) ? attrValue : false;
	},

	/**
	* @param  {*} attrValue
	* @return {*}
	*/
	filterSimpletext: function(attrValue)
	{
		return /^[-\w+., ]+$/.test(attrValue) ? attrValue : false;
	},

	/**
	* @param  {*} attrValue
	* @return {*}
	*/
	filterUint: function(attrValue)
	{
		return /^(?:0|[1-9]\d*)$/.test(attrValue) ? attrValue : false;
	},

	/**
	* @param  {*} attrValue
	* @param  {!Object} urlConfig
	* @param  {Logger} logger
	* @return {*}
	*/
	filterUrl: function(attrValue, urlConfig, logger)
	{
		/**
		* Trim the URL to conform with HTML5
		* @link http://dev.w3.org/html5/spec/links.html#attr-hyperlink-href
		*/
		attrValue = attrValue.replace(/^\s+/, '').replace(/\s+$/, '');

		/**
		* @type {!boolean} Whether to remove the scheme part of the URL
		*/
		var removeScheme = false;

		/**
		* @type {!boolean} Whether to validate the scheme part of the URL
		*/
		var validateScheme = true;

		if (attrValue.substr(0, 2) === '//' && !urlConfig.requireScheme)
		{
			attrValue      = 'http:' + attrValue;
			removeScheme   = true;
			validateScheme = false;
		}

		// Encode some potentially troublesome chars
		attrValue = BuiltInFilters.sanitizeUrl(attrValue);

		// Parse the URL... kinda
		var m =/^([a-z\d]+):\/\/(?:[^/]*@)?([^/]+)(?:\/.*)?$/i.exec(attrValue);

		if (!m)
		{
			return false;
		}

		if (validateScheme && !urlConfig.allowedSchemes.test(m[1]))
		{
			if (logger)
			{
				logger.err(
					'URL scheme is not allowed',
					{'attrValue': attrValue, 'scheme': m[1]}
				);
			}

			return false;
		}

		/**
		* Normalize the domain label separators and remove trailing dots
		* @link http://url.spec.whatwg.org/#domain-label-separators
		*/
		var host = m[2].replace(/[\u3002\uff0e\uff61]/g, '.').replace(/\.+$/g, '');

		if ((urlConfig.disallowedHosts && urlConfig.disallowedHosts.test(host))
		 || (urlConfig.restrictedHosts && !urlConfig.restrictedHosts.test(host)))
		{
			if (logger)
			{
				logger.err(
					'URL host is not allowed',
					{'attrValue': attrValue, 'host': m[2]}
				);
			}

			return false;
		}

		// Normalize scheme, or remove if applicable
		var pos = attrValue.indexOf(':');

		if (removeScheme)
		{
			attrValue = attrValue.substr(pos + 1);
		}
		else
		{
			/**
			* @link http://tools.ietf.org/html/rfc3986#section-3.1
			*
			* 'An implementation should accept uppercase letters as equivalent to lowercase in
			* scheme names (e.g., allow "HTTP" as well as "http") for the sake of robustness but
			* should only produce lowercase scheme names for consistency.'
			*/
			attrValue = attrValue.substr(0, pos).toLowerCase() + attrValue.substr(pos);
		}

		return attrValue;
	},

	/**
	* Parse a URL and return its components
	*
	* Similar to PHP's own parse_url() except that all parts are always returned
	*
	* @param  {!string} url Original URL
	* @return {!Object}
	*/
	parseUrl: function(url)
	{
		var regexp = /^(?:([a-z][-+.\w]*):)?(?:\/\/(?:([^:\/?#]*)(?::([^\/?#]*)?)?@)?(?:(\[[a-f\d:]+\]|[^:\/?#]+)(?::(\d*))?)?(?![^\/?#]))?([^?#]*)(?:\?([^#]*))?(?:#(.*))?$/i;

		// NOTE: this regexp always matches because of the last three captures
		var m = regexp.exec(url);

		var parts = {
			scheme   : (m[1] > '') ? m[1] : '',
			user     : (m[2] > '') ? m[2] : '',
			pass     : (m[3] > '') ? m[3] : '',
			host     : (m[4] > '') ? m[4] : '',
			port     : (m[5] > '') ? m[5] : '',
			path     : (m[6] > '') ? m[6] : '',
			query    : (m[7] > '') ? m[7] : '',
			fragment : (m[8] > '') ? m[8] : ''
		};

		/**
		* @link http://tools.ietf.org/html/rfc3986#section-3.1
		*
		* 'An implementation should accept uppercase letters as equivalent to lowercase in
		* scheme names (e.g., allow "HTTP" as well as "http") for the sake of robustness but
		* should only produce lowercase scheme names for consistency.'
		*/
		parts.scheme = parts.scheme.toLowerCase();

		/**
		* Normalize the domain label separators and remove trailing dots
		* @link http://url.spec.whatwg.org/#domain-label-separators
		*/
		parts.host = parts.host.replace(/[\u3002\uff0e\uff61]/g, '.').replace(/\.+$/g, '');

		return parts;
	},

	/**
	* Sanitize a URL for safe use regardless of context
	*
	* This method URL-encodes some sensitive characters in case someone would want to use the URL in
	* some JavaScript thingy, or in CSS. We also encode illegal characters
	*
	* " and ' to prevent breaking out of quotes (JavaScript or otherwise)
	* ( and ) to prevent the use of functions in JavaScript (eval()) or CSS (expression())
	* < and > to prevent breaking out of <script>
	* \r and \n because they're illegal in JavaScript
	* [ and ] because the W3 validator rejects them and they "should" be escaped as per RFC 3986
	* Non-ASCII characters as per RFC 3986
	* Control codes and spaces, as per RFC 3986
	*
	* @link http://sla.ckers.org/forum/read.php?2,51478
	* @link http://timelessrepo.com/json-isnt-a-javascript-subset
	* @link http://www.ietf.org/rfc/rfc3986.txt
	* @link http://stackoverflow.com/a/1547922
	*
	* @param  {!string} url Original URL
	* @return {!string}     Sanitized URL
	*/
	sanitizeUrl: function(url)
	{
		return url.replace(/["'()<>[\]\x00-\x20\x7F]+/g, escape).replace(/[^\u0020-\u007E]+/g, encodeURIComponent);
	}
}