<?php
/**
 * Copyright (C) 2015 David Young
 *
 * Defines a JSON response
 */
namespace RDev\HTTP\Responses;
use ArrayObject;
use InvalidArgumentException;

class JSONResponse extends Response
{
    /**
     * @param mixed $content The content of the response
     * @param int $statusCode The HTTP status code
     * @param array $headers The headers to set
     * @throws InvalidArgumentException Thrown if the content is not of the correct type
     */
    public function __construct($content = [], $statusCode = ResponseHeaders::HTTP_OK, array $headers = [])
    {
        $this->setContent($content);
        $this->headers = new ResponseHeaders($headers);
        $this->headers->set("Content-Type", ResponseHeaders::CONTENT_TYPE_JSON);
        $this->setStatusCode($statusCode);
    }

    /**
     * {@inheritdoc}
     * @param mixed $content The content to set
     * @throws InvalidArgumentException Thrown if the input could not be JSON encoded
     */
    public function setContent($content)
    {
        if($content instanceof ArrayObject)
        {
            $content = $content->getArrayCopy();
        }

        $json = json_encode($content);

        if(json_last_error() !== JSON_ERROR_NONE)
        {
            throw new InvalidArgumentException("Failed to JSON encode content: " . json_last_error_msg());
        }

        parent::setContent($json);
    }
} 