<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 06.05.17
 * Time: 11:33 AM
 */

namespace rollun\test\Middleware;


use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use rollun\actionrender\Renderer\Html\HtmlParamResolver;

class HelloAction implements MiddlewareInterface
{

    /**
     * Process an incoming server request and return a response, optionally delegating
     * to the next middleware component to create the response.
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface $delegate
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $params = $request->getQueryParams();
        $str = isset($params['str']) ? $params['str'] : "World";
        $request = $request->withAttribute('responseData', ['str' => $str]);
        $request = $request->withAttribute(HtmlParamResolver::KEY_ATTRIBUTE_TEMPLATE_NAME, 'test-app::home-page');
        $response = $delegate->process($request);
        return $response;
    }
}