<?php

namespace Nettixcode\Framework\Collectors;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\DataCollectorInterface;
use DebugBar\DataCollector\Renderable;
use Nettixcode\Framework\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Arr;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

class HttpRequestCollector extends DataCollector implements DataCollectorInterface, Renderable
{
    protected $request;
    protected $response;
    protected $session;
    protected $currentRequestId;
    protected $hiddens;
    protected $cloner;
    protected $dumper;

    public function __construct(Request $request, Response $response, $session = null, $currentRequestId = null, $hiddens = [])
    {
        $this->request = $request;
        $this->response = $response;
        $this->session = $session;
        $this->currentRequestId = $currentRequestId;
        $this->hiddens = array_merge($hiddens, [
            'request_request.password',
            'request_headers.php-auth-pw.0',
        ]);

        // Initialize VarDumper components
        $this->cloner = new VarCloner();
        $this->dumper = new HtmlDumper();
    }

    public function getName()
    {
        return 'Http_Request';
    }

    public function getWidgets()
    {
        return [
            "Http_Request" => [
                "icon" => "tags",
                "widget" => "PhpDebugBar.Widgets.HtmlVariableListWidget",
                "map" => "Http_Request",
                "default" => "{}"
            ]
        ];
    }

    public function collect()
    {
        $request = $this->request->getIlluminateRequest();
        $response = $this->response;

        $responseHeaders = $response->headers->all();
        $cookies = [];
        foreach ($response->headers->getCookies() as $cookie) {
            $cookies[] = $this->getCookieHeader(
                $cookie->getName(),
                $cookie->getValue(),
                $cookie->getExpiresTime(),
                $cookie->getPath(),
                $cookie->getDomain(),
                $cookie->isSecure(),
                $cookie->isHttpOnly()
            );
        }
        if (count($cookies) > 0) {
            $responseHeaders['Set-Cookie'] = $cookies;
        }

        $statusCode = $response->getStatusCode();

        $data = [
            'path_info' => $request->getPathInfo(),
            'status_code' => $statusCode,
            'status_text' => isset(Response::$statusTexts[$statusCode]) ? Response::$statusTexts[$statusCode] : '',
            'format' => $request->getRequestFormat(),
            'content_type' => $response->headers->get('Content-Type') ? $response->headers->get('Content-Type') : 'text/html',
            'request_query' => $request->query->all(),
            'request_request' => $request->request->all(),
            'request_headers' => $request->headers->all(),
            'request_cookies' => $request->cookies->all(),
            'response_headers' => $responseHeaders,
        ];

        if ($this->session) {
            $data['session_attributes'] = $this->session->all();
        }

        foreach ($this->hiddens as $key) {
            if (Arr::has($data, $key)) {
                Arr::set($data, $key, '******');
            }
        }

        foreach ($data as $key => $var) {
            if (!is_string($data[$key])) {
                $data[$key] = $this->dumpVar($var);
            } else {
                $data[$key] = htmlspecialchars($data[$key], ENT_QUOTES, 'UTF-8');
            }
        }

        return $data;
    }

    protected function dumpVar($var)
    {
        return $this->dumper->dump($this->cloner->cloneVar($var), true);
    }

    private function getCookieHeader($name, $value, $expires, $path, $domain, $secure, $httponly)
    {
        $cookie = sprintf('%s=%s', $name, urlencode($value));

        if (0 !== $expires) {
            if (is_numeric($expires)) {
                $expires = (int) $expires;
            } elseif ($expires instanceof \DateTime) {
                $expires = $expires->getTimestamp();
            } else {
                $expires = strtotime($expires);
                if (false === $expires || -1 == $expires) {
                    throw new \InvalidArgumentException(sprintf('The "expires" cookie parameter is not valid.', $expires));
                }
            }

            $cookie .= '; expires=' . substr(\DateTime::createFromFormat('U', $expires, new \DateTimeZone('UTC'))->format('D, d-M-Y H:i:s T'), 0, -5);
        }

        if ($domain) {
            $cookie .= '; domain=' . $domain;
        }

        $cookie .= '; path=' . $path;

        if ($secure) {
            $cookie .= '; secure';
        }

        if ($httponly) {
            $cookie .= '; httponly';
        }

        return $cookie;
    }
}
