<?php

namespace Dev\ApiDocBundle\Registry;

use Dev\ApiDocBundle\Model\Component;
use Dev\ApiDocBundle\Model\Operation;
use function array_key_first;

class OperationRegistry extends Registry
{
    public function toArray(array $protoData = []): array
    {
        $operations = $this->models;
        $data = [];

        /* @var Operation $operation */
        /* @var string|Component $response */
        foreach ($operations as $operation) {
            $pathData = [];
            if (null !== $description = $operation->description) {
                $pathData['description'] = $description;
            }
            $pathData['operationId'] = $operation->id;
            $responses = [];
            foreach ($operation->responses as $status => $response) {
                if ($response instanceof Component){
                    $content = $response->headers['Content-Type'];
                    $responses[$response->status]['description'] = $operation->id;
                    $responses[$response->status]['content'][$content]['schema']['$ref'] =
                        '#/components/schemas/'.$response->id;
                } else{
                    $responses[$status]['$ref'] =   '#/components/responses/'.$response;
                }
            }

            if (null !== $operation->request) {
                $request = [];
                $content = 'application/json';
                $request['content'][$content]['schema']['$ref'] = '#/components/schemas/'.$operation->request->id;
                $pathData['requestBody'] = $request;
            }

            if (!empty($security = $operation->security)) {
                if (isset($security['default']) && isset($protoData['components']['securitySchemes'])) {
                    $firstSecurity = array_key_first($protoData['components']['securitySchemes']);
                    $security[$firstSecurity] = $security['default'];
                    unset($security['default']);
                }
                $pathData['security'] = [$security];
            }

            $pathData['responses'] = $responses;
            $data[$operation->path][strtolower($operation->method)] = $pathData;
        }

        return $data;
    }
}