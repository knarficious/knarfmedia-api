<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use App\Entity\Publication;


class PublicationProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private Security $security
        ) {}
    
    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): Publication
    {
        $data->setAuthor($this->security->getUser());
        
        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
