<?php
// +----------------------------------------------------------------------
// | Leaps Framework [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011-2014 Leaps Team (http://www.tintsoft.com)
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author XuTongle <xutongle@gmail.com>
// +----------------------------------------------------------------------
namespace Leaps;

class Whois
{
	/**
	 * 获取 Whois Serve
	 *
	 * @param string $ip
	 */
	public function getWhoisServer($ip)
	{
		$w = $this->get_whois_from_server ( 'whois.iana.org', $ip );

		preg_match ( '@whois\.[\w\.]*@si', $w, $data );
		return $w;
	}

	/**
	 * 发起Socket请求
	 *
	 * @param string $server
	 * @param string $ip
	 * @return string
	 */
	private function getWhois($server, $ip)
	{
		$data = '';
		$f = fsockopen ( $server, 43, $errno, $errstr, 3 ); // Open a new connection
		if (! $f) {
			return '';
		}
		if (! stream_set_timeout ( $f, 3 )) {
			die ( 'Unable to set set_timeout' ); // Did this solve the problem ?
		}
		if ($f) {
			fputs ( $f, "$ip\r\n" );
		}
		if (! stream_set_timeout ( $f, 3 )) {
			die ( 'Unable to stream_set_timeout' ); // Did this solve the problem ?
		}
		stream_set_blocking ( $f, 0 );
		if ($f) {
			while ( ! feof ( $f ) ) {
				$data .= fread ( $f, 128 );
			}
		}
		return $data;
	}

	/**
	 * 获取Whois服务器列表
	 * @return multitype:string
	 */
	public function getServers()
	{
		return [
				'abogado','ac','academy','accountants','active','actor','ad','adult','ae','aero','af','ag','agency','ai','airforce',
				'com' => 'whois.verisign-grs.com',
				'net' => 'whois.verisign-grs.com',
				'cn' => 'whois.cnnic.cn',
				'org' => 'whois.pir.org',
				'biz' => 'whois.biz',
				'tv' => 'tvwhois.verisign-grs.com',
				'.中国' => 'cwhois.cnnic.cn'
		];
	}
}