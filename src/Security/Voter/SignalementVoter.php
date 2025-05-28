<?php
// src/Security/Voter/SignalementVoter.php
namespace App\Security\Voter;

use App\Entity\Signalement;
use App\Entity\Utilisateur;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SignalementVoter extends Voter
{
    // Définir les actions possibles sur un signalement
    const VIEW = 'view';
    const EDIT = 'edit';
    const DELETE = 'delete';

    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Si l'attribut n'est pas l'un de ceux que nous supportons, return false
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])) {
            return false;
        }

        // Seuls les objets Signalement sont supportés
        if (!$subject instanceof Signalement) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // L'utilisateur doit être connecté
        if (!$user instanceof Utilisateur) {
            return false;
        }

        /** @var Signalement $signalement */
        $signalement = $subject;

        switch ($attribute) {
            case self::VIEW:
                return $this->canView($signalement, $user);
            case self::EDIT:
                return $this->canEdit($signalement, $user);
            case self::DELETE:
                return $this->canDelete($signalement, $user);
        }

        throw new \LogicException('Cette ligne ne devrait jamais être atteinte!');
    }

    private function canView(Signalement $signalement, Utilisateur $user): bool
    {
        // Si l'utilisateur est admin ou agent, il peut voir tous les signalements
        if ($this->security->isGranted('ROLE_ADMIN') ||
            $this->security->isGranted('ROLE_AGENT') ||
            $this->security->isGranted('ROLE_USER')) {
            return true;
        }

        // L'utilisateur peut voir ses propres signalements
        if ($signalement->getUtilisateur() === $user) {
            return true;
        }

        // L'utilisateur peut voir les signalements validés/publics
        if ($signalement->getEtatValidation() === 'valide') {
            return true;
        }

        // Par défaut, refuser l'accès
        return false;
    }

    private function canEdit(Signalement $signalement, Utilisateur $user): bool
    {
        // Seul l'administrateur peut modifier n'importe quel signalement
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // Les agents peuvent modifier les signalements
        // Note: la relation avec l'agent n'existe pas dans le modèle actuel
        // Cette vérification doit être ajustée selon votre modèle de données
        if ($this->security->isGranted('ROLE_AGENT')) {
            // Vous pourriez ajouter d'autres vérifications ici selon votre logique métier
            return true;
        }

        // L'utilisateur peut modifier ses propres signalements si le statut le permet
        $statut = $signalement->getStatut();
        if ($signalement->getUtilisateur() === $user && $statut !== null && $statut === StatutSignalement::NOUVEAU) {
            return true;
        }

        return false;
    }

    private function canDelete(Signalement $signalement, Utilisateur $user): bool
    {
        // Seul l'administrateur peut supprimer un signalement
        return $this->security->isGranted('ROLE_ADMIN');
    }
}