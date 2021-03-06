<?php

declare(strict_types=1);

namespace Elewant\AppBundle\Controller;

use Elewant\AppBundle\Entity\Herd;
use Elewant\AppBundle\Repository\HerdRepository;
use Elewant\Herding\Model\BreedCollection;
use Elewant\Herding\Model\Commands\AbandonElePHPant;
use Elewant\Herding\Model\Commands\AdoptElePHPant;
use Elewant\Herding\Model\Commands\DesireBreed;
use Elewant\Herding\Model\Commands\EliminateDesireForBreed;
use Elewant\UserBundle\Entity\User;
use Prooph\ServiceBus\CommandBus;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Route("/herd", options={"expose"=true})
 * @Security("has_role('ROLE_USER')")
 */
class HerdController extends Controller
{

    /**
     * @Route("/tending", name="herd_tending")
     */
    public function herdTendingAction(UserInterface $user): Response
    {
        $herd = $this->getHerd($user);

        $data = [
            'user'              => $user,
            'herd'              => $herd,
            'allUnwantedBreeds' => $herd->desiredBreeds()->isMissingBreedsWhenComparedTo(BreedCollection::all()),
            'regularBreeds'     => BreedCollection::allRegular(),
            'largeBreeds'       => BreedCollection::allLarge(),
        ];

        return $this->render('ElewantAppBundle:Herd:tending.html.twig', $data);
    }

    /**
     * @Route("/adopt/{breed}", name="herd_adopt_breed")
     */
    public function adoptElePHPantAction(UserInterface $user, string $breed): Response
    {
        $herd = $this->getHerd($user);

        /** @var CommandBus $commandBus */
        $commandBus = $this->get('prooph_service_bus.herding_command_bus');
        $command    = AdoptElePHPant::byHerd($herd->herdId(), $breed);

        $commandBus->dispatch($command);

        return new JsonResponse('adopt_breed_underway');
    }

    /**
     * @Route("/abandon/{breed}", name="herd_abandon_breed")
     */
    public function abandonElePHPantAction(UserInterface $user, string $breed): Response
    {
        $herd = $this->getHerd($user);

        /** @var CommandBus $commandBus */
        $commandBus = $this->get('prooph_service_bus.herding_command_bus');
        $command    = AbandonElePHPant::byHerd($herd->herdId(), $breed);

        $commandBus->dispatch($command);

        return new JsonResponse('abandon_breed_underway');
    }

    /**
     * @Route("/desire/{breed}", name="herd_desire_breed")
     */
    public function desireBreedAction(UserInterface $user, string $breed): Response
    {
        $herd = $this->getHerd($user);

        /** @var CommandBus $commandBus */
        $commandBus = $this->get('prooph_service_bus.herding_command_bus');
        $command    = DesireBreed::byHerd($herd->herdId(), $breed);

        $commandBus->dispatch($command);

        return new JsonResponse('desire_breed_underway');
    }

    /**
     * @Route("/eliminate-desire-for/{breed}", name="herd_eliminate_desire_for_breed")
     */
    public function eliminateDesireForBreedAction(UserInterface $user, string $breed): Response
    {
        $herd = $this->getHerd($user);

        /** @var CommandBus $commandBus */
        $commandBus = $this->get('prooph_service_bus.herding_command_bus');
        $command    = EliminateDesireForBreed::byHerd($herd->herdId(), $breed);

        $commandBus->dispatch($command);

        return new JsonResponse('desire_breed_underway');
    }

    /**
     * @param User|UserInterface $user
     *
     * @return Herd
     * @throws NotFoundHttpException
     */
    private function getHerd(User $user): Herd
    {
        /** @var HerdRepository $herdRepository */
        $herdRepository = $this->get('elewant.herd.herd_repository');
        $herd           = $herdRepository->findOneByShepherdId($user->shepherdId());

        if ($herd === null) {
            throw $this->createNotFoundException('error.herd.herd-not-found');
        }

        return $herd;
    }
}
