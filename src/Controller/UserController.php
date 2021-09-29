<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Gedmo\Loggable\Entity\LogEntry;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("ROLE_ADMIN")
 * @Route("/admin/users")
 */
class UserController extends AbstractController
{
    /**
     * @Route("/", name="user_index", methods={"GET"})
     */
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findBy([], ['lastLogin' => 'DESC']),
        ]);
    }

    /**
     * @Route("/new", name="user_new", methods={"GET", "POST"})
     */
    public function new(Request $request, UserPasswordHasherInterface $passwordEncoder): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            if ($form['plainPassword']->getData()) {
                $user->setPassword($passwordEncoder->hashPassword(
                    $user,
                    $form['plainPassword']->getData()
                ));
            }
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', sprintf('%s created!', $user));

            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="user_show", methods={"GET"})
     */
    public function show(User $user): Response
    {
        $logEntryRepository = $this->getDoctrine()->getRepository(LogEntry::class);
        $logs = $logEntryRepository->findBy(['username' => $user->getUsername()], ['loggedAt' => 'DESC'], 1000);

        return $this->render('user/show.html.twig', [
            'user' => $user,
            'logs' => $logs,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="user_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, User $user, UserPasswordHasherInterface $passwordEncoder): Response
    {
        $form = $this->createForm(UserType::class, $user, ['require_password' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();
            if ($form['plainPassword']->getData()) {
                $user->setPassword($passwordEncoder->hashPassword(
                    $user,
                    $form['plainPassword']->getData()
                ));
            }
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', sprintf('%s updated!', $user));

            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/disable-2fa", name="user_disable_two_factor", methods={"POST"})
     */
    public function disableTwoFactor(Request $request, User $user, LoggerInterface $logger): Response
    {
        if ($this->isCsrfTokenValid('disableTwoFactor'.$user->getId(), $request->request->get('_token'))) {
            $user->setTotpSecret(null);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', 'Two-Factor Security disabled.');
            $logger->info(sprintf(
                '[SECURITY] %s disabled Two-Factor Security on %s',
                (string) $this->getUser(),
                (string) $user
            ));
        }

        return $this->redirectToRoute('user_show', ['id' => $user->getId()]);
    }

    /**
     * @Route("/{id}", name="user_delete", methods={"POST"})
     */
    public function delete(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($user);
            $entityManager->flush();
            $this->addFlash('success', sprintf('%s deleted!', $user));
        }

        return $this->redirectToRoute('user_index');
    }
}
