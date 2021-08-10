<?php

declare(strict_types=1);

namespace Imi\Server\Http\Message\Proxy;

use Imi\Bean\Annotation\Bean;
use Imi\RequestContextProxy\Annotation\RequestContextProxy;
use Imi\RequestContextProxy\BaseRequestContextProxy;
use Imi\Util\Socket\IPEndPoint;

/**
 * @Bean(name="HttpRequestProxy")
 * @RequestContextProxy(class="Imi\Server\Http\Message\Contract\IHttpRequest", name="request")
 *
 * @method mixed             getCookie(string $name, $default = NULL)
 * @method static            mixed getCookie(string $name, $default = NULL)
 * @method static            setCookieParams(array $cookies)
 * @method static            static setCookieParams(array $cookies)
 * @method static            setQueryParams(array $query)
 * @method static            static setQueryParams(array $query)
 * @method static            setUploadedFiles(array $uploadedFiles)
 * @method static            static setUploadedFiles(array $uploadedFiles)
 * @method static            setParsedBody($data)
 * @method static            static setParsedBody($data)
 * @method static            setAttribute(string $name, $value)
 * @method static            static setAttribute(string $name, $value)
 * @method static            removeAttribute(string $name)
 * @method static            static removeAttribute(string $name)
 * @method mixed             get(?string $name = NULL, $default = NULL)
 * @method static            mixed get(?string $name = NULL, $default = NULL)
 * @method mixed             post(?string $name = NULL, $default = NULL)
 * @method static            mixed post(?string $name = NULL, $default = NULL)
 * @method bool              hasGet(string $name)
 * @method static            bool hasGet(string $name)
 * @method bool              hasPost(string $name)
 * @method static            bool hasPost(string $name)
 * @method mixed             request(?string $name = NULL, $default = NULL)
 * @method static            mixed request(?string $name = NULL, $default = NULL)
 * @method bool              hasRequest(string $name)
 * @method static            bool hasRequest(string $name)
 * @method static            withGet(array $get)
 * @method static            static withGet(array $get)
 * @method static            setGet(array $get)
 * @method static            static setGet(array $get)
 * @method static            withPost(array $post)
 * @method static            static withPost(array $post)
 * @method static            setPost(array $post)
 * @method static            static setPost(array $post)
 * @method static            withRequest(array $request)
 * @method static            static withRequest(array $request)
 * @method static            setRequest(array $request)
 * @method static            static setRequest(array $request)
 * @method array             getServerParams()
 * @method static            array getServerParams()
 * @method array             getCookieParams()
 * @method static            array getCookieParams()
 * @method static            withCookieParams(array $cookies)
 * @method static            static withCookieParams(array $cookies)
 * @method array             getQueryParams()
 * @method static            array getQueryParams()
 * @method static            withQueryParams(array $query)
 * @method static            static withQueryParams(array $query)
 * @method array             getUploadedFiles()
 * @method static            array getUploadedFiles()
 * @method static            withUploadedFiles(array $uploadedFiles)
 * @method static            static withUploadedFiles(array $uploadedFiles)
 * @method array|object|null getParsedBody()
 * @method static            array|object|null getParsedBody()
 * @method static            withParsedBody($data)
 * @method static            static withParsedBody($data)
 * @method array             getAttributes()
 * @method static            array getAttributes()
 * @method mixed             getAttribute($name, $default = NULL)
 * @method static            mixed getAttribute($name, $default = NULL)
 * @method static            withAttribute($name, $value)
 * @method static            static withAttribute($name, $value)
 * @method static            withoutAttribute($name)
 * @method static            static withoutAttribute($name)
 * @method string            getRequestTarget()
 * @method static            string getRequestTarget()
 * @method static            withRequestTarget($requestTarget)
 * @method static            static withRequestTarget($requestTarget)
 * @method string            getMethod()
 * @method static            string getMethod()
 * @method static            withMethod($method)
 * @method static            static withMethod($method)
 * @method UriInterface      getUri()
 * @method static            UriInterface getUri()
 * @method static            withUri(\Psr\Http\Message\UriInterface $uri, $preserveHost = false)
 * @method static            static withUri(\Psr\Http\Message\UriInterface $uri, $preserveHost = false)
 * @method string            getProtocolVersion()
 * @method static            string getProtocolVersion()
 * @method static            withProtocolVersion($version)
 * @method static            static withProtocolVersion($version)
 * @method string[][]        getHeaders()
 * @method static            string[][] getHeaders()
 * @method bool              hasHeader($name)
 * @method static            bool hasHeader($name)
 * @method string[]          getHeader($name)
 * @method static            string[] getHeader($name)
 * @method string            getHeaderLine($name)
 * @method static            string getHeaderLine($name)
 * @method static            withHeader($name, $value)
 * @method static            static withHeader($name, $value)
 * @method static            withAddedHeader($name, $value)
 * @method static            static withAddedHeader($name, $value)
 * @method static            withoutHeader($name)
 * @method static            static withoutHeader($name)
 * @method StreamInterface   getBody()
 * @method static            StreamInterface getBody()
 * @method static            withBody(\Psr\Http\Message\StreamInterface $body)
 * @method static            static withBody(\Psr\Http\Message\StreamInterface $body)
 * @method static            setRequestTarget($requestTarget)
 * @method static            static setRequestTarget($requestTarget)
 * @method static            setMethod(string $method)
 * @method static            static setMethod(string $method)
 * @method static            setUri(\Psr\Http\Message\UriInterface $uri, bool $preserveHost = false)
 * @method static            static setUri(\Psr\Http\Message\UriInterface $uri, bool $preserveHost = false)
 * @method IPEndPoint        getClientAddress()
 * @method static            IPEndPoint getClientAddress()
 */
class RequestProxy extends BaseRequestContextProxy implements \Imi\Server\Http\Message\Contract\IHttpRequest
{
    /**
     * {@inheritDoc}
     */
    public function getCookie(string $name, $default = null)
    {
        return self::__getProxyInstance()->getCookie($name, $default);
    }

    /**
     * {@inheritDoc}
     */
    public function setCookieParams(array $cookies): \Imi\Util\Http\Contract\IServerRequest
    {
        return self::__getProxyInstance()->setCookieParams($cookies);
    }

    /**
     * {@inheritDoc}
     */
    public function setQueryParams(array $query): \Imi\Util\Http\Contract\IServerRequest
    {
        return self::__getProxyInstance()->setQueryParams($query);
    }

    /**
     * {@inheritDoc}
     */
    public function setUploadedFiles(array $uploadedFiles): \Imi\Util\Http\Contract\IServerRequest
    {
        return self::__getProxyInstance()->setUploadedFiles($uploadedFiles);
    }

    /**
     * {@inheritDoc}
     */
    public function setParsedBody($data): \Imi\Util\Http\Contract\IServerRequest
    {
        return self::__getProxyInstance()->setParsedBody($data);
    }

    /**
     * {@inheritDoc}
     */
    public function setAttribute(string $name, $value): \Imi\Util\Http\Contract\IServerRequest
    {
        return self::__getProxyInstance()->setAttribute($name, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function removeAttribute(string $name): \Imi\Util\Http\Contract\IServerRequest
    {
        return self::__getProxyInstance()->removeAttribute($name);
    }

    /**
     * {@inheritDoc}
     */
    public function get(?string $name = null, $default = null)
    {
        return self::__getProxyInstance()->get($name, $default);
    }

    /**
     * {@inheritDoc}
     */
    public function post(?string $name = null, $default = null)
    {
        return self::__getProxyInstance()->post($name, $default);
    }

    /**
     * {@inheritDoc}
     */
    public function hasGet(string $name): bool
    {
        return self::__getProxyInstance()->hasGet($name);
    }

    /**
     * {@inheritDoc}
     */
    public function hasPost(string $name): bool
    {
        return self::__getProxyInstance()->hasPost($name);
    }

    /**
     * {@inheritDoc}
     */
    public function request(?string $name = null, $default = null)
    {
        return self::__getProxyInstance()->request($name, $default);
    }

    /**
     * {@inheritDoc}
     */
    public function hasRequest(string $name): bool
    {
        return self::__getProxyInstance()->hasRequest($name);
    }

    /**
     * {@inheritDoc}
     */
    public function withGet(array $get): \Imi\Util\Http\Contract\IServerRequest
    {
        return self::__getProxyInstance()->withGet($get);
    }

    /**
     * {@inheritDoc}
     */
    public function setGet(array $get): \Imi\Util\Http\Contract\IServerRequest
    {
        return self::__getProxyInstance()->setGet($get);
    }

    /**
     * {@inheritDoc}
     */
    public function withPost(array $post): \Imi\Util\Http\Contract\IServerRequest
    {
        return self::__getProxyInstance()->withPost($post);
    }

    /**
     * {@inheritDoc}
     */
    public function setPost(array $post): \Imi\Util\Http\Contract\IServerRequest
    {
        return self::__getProxyInstance()->setPost($post);
    }

    /**
     * {@inheritDoc}
     */
    public function withRequest(array $request): \Imi\Util\Http\Contract\IServerRequest
    {
        return self::__getProxyInstance()->withRequest($request);
    }

    /**
     * {@inheritDoc}
     */
    public function setRequest(array $request): \Imi\Util\Http\Contract\IServerRequest
    {
        return self::__getProxyInstance()->setRequest($request);
    }

    /**
     * {@inheritDoc}
     */
    public function getServerParams()
    {
        return self::__getProxyInstance()->getServerParams(...\func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function getCookieParams()
    {
        return self::__getProxyInstance()->getCookieParams(...\func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function withCookieParams(array $cookies)
    {
        return self::__getProxyInstance()->withCookieParams($cookies);
    }

    /**
     * {@inheritDoc}
     */
    public function getQueryParams()
    {
        return self::__getProxyInstance()->getQueryParams(...\func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function withQueryParams(array $query)
    {
        return self::__getProxyInstance()->withQueryParams($query);
    }

    /**
     * {@inheritDoc}
     */
    public function getUploadedFiles()
    {
        return self::__getProxyInstance()->getUploadedFiles(...\func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        return self::__getProxyInstance()->withUploadedFiles($uploadedFiles);
    }

    /**
     * {@inheritDoc}
     */
    public function getParsedBody()
    {
        return self::__getProxyInstance()->getParsedBody(...\func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function withParsedBody($data)
    {
        return self::__getProxyInstance()->withParsedBody($data);
    }

    /**
     * {@inheritDoc}
     */
    public function getAttributes()
    {
        return self::__getProxyInstance()->getAttributes(...\func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function getAttribute($name, $default = null)
    {
        return self::__getProxyInstance()->getAttribute($name, $default);
    }

    /**
     * {@inheritDoc}
     */
    public function withAttribute($name, $value)
    {
        return self::__getProxyInstance()->withAttribute($name, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function withoutAttribute($name)
    {
        return self::__getProxyInstance()->withoutAttribute($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getRequestTarget()
    {
        return self::__getProxyInstance()->getRequestTarget(...\func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function withRequestTarget($requestTarget)
    {
        return self::__getProxyInstance()->withRequestTarget($requestTarget);
    }

    /**
     * {@inheritDoc}
     */
    public function getMethod()
    {
        return self::__getProxyInstance()->getMethod(...\func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function withMethod($method)
    {
        return self::__getProxyInstance()->withMethod($method);
    }

    /**
     * {@inheritDoc}
     */
    public function getUri()
    {
        return self::__getProxyInstance()->getUri(...\func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function withUri(\Psr\Http\Message\UriInterface $uri, $preserveHost = false)
    {
        return self::__getProxyInstance()->withUri($uri, $preserveHost);
    }

    /**
     * {@inheritDoc}
     */
    public function getProtocolVersion()
    {
        return self::__getProxyInstance()->getProtocolVersion(...\func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function withProtocolVersion($version)
    {
        return self::__getProxyInstance()->withProtocolVersion($version);
    }

    /**
     * {@inheritDoc}
     */
    public function getHeaders()
    {
        return self::__getProxyInstance()->getHeaders(...\func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function hasHeader($name)
    {
        return self::__getProxyInstance()->hasHeader($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getHeader($name)
    {
        return self::__getProxyInstance()->getHeader($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getHeaderLine($name)
    {
        return self::__getProxyInstance()->getHeaderLine($name);
    }

    /**
     * {@inheritDoc}
     */
    public function withHeader($name, $value)
    {
        return self::__getProxyInstance()->withHeader($name, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function withAddedHeader($name, $value)
    {
        return self::__getProxyInstance()->withAddedHeader($name, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function withoutHeader($name)
    {
        return self::__getProxyInstance()->withoutHeader($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getBody()
    {
        return self::__getProxyInstance()->getBody(...\func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function withBody(\Psr\Http\Message\StreamInterface $body)
    {
        return self::__getProxyInstance()->withBody($body);
    }

    /**
     * {@inheritDoc}
     */
    public function setRequestTarget($requestTarget): \Imi\Util\Http\Contract\IRequest
    {
        return self::__getProxyInstance()->setRequestTarget($requestTarget);
    }

    /**
     * {@inheritDoc}
     */
    public function setMethod(string $method): \Imi\Util\Http\Contract\IRequest
    {
        return self::__getProxyInstance()->setMethod($method);
    }

    /**
     * {@inheritDoc}
     */
    public function setUri(\Psr\Http\Message\UriInterface $uri, bool $preserveHost = false): \Imi\Util\Http\Contract\IRequest
    {
        return self::__getProxyInstance()->setUri($uri, $preserveHost);
    }

    /**
     * {@inheritDoc}
     */
    public function getClientAddress(): IPEndPoint
    {
        return self::__getProxyInstance()->getClientAddress();
    }
}
