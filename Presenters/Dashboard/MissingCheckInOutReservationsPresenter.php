<?php

require_once(ROOT_DIR . 'Controls/Dashboard/MissingCheckInOutReservations.php');

class MissingCheckInOutReservationsPresenter {
    /**
     * @var IMissingCheckInOutReservationsControl
     */
    private $control;

    /**
     * @var IReservationViewRepository
     */
    private $repository;

    /**
     * @var int
     */
    private $searchUserId = ReservationViewRepository::ALL_USERS;

    /**
     * @var int
     */
    private $searchUserLevel = ReservationUserLevel::ALL;

    public function __construct(IMissingCheckInOutReservationsControl $control, IReservationViewRepository $repository)
    {
        $this->control = $control;
        $this->repository = $repository;
    }

    public function SetSearchCriteria($userId, $userLevel)
    {
        $this->searchUserId = $userId;
        $this->searchUserLevel = $userLevel;
    }

    public function PageLoad()
    {
        $user = ServiceLocator::GetServer()->GetUserSession();
        $timezone = $user->Timezone;

        $now = Date::Now();
        $today = $now->ToTimezone($timezone)->GetDate();
        $dayOfWeek = $today->Weekday();

        $firstDate = $now->AddDays(-13+(6-$dayOfWeek)+1);
        $yesterday = $today->AddDays(-1);

        $startOfPreviousWeek = $today->AddDays(-(7+$dayOfWeek));

        $consolidated = [];

        if (ServiceLocator::GetServer()->GetUserSession()->IsAdmin){
            $consolidated = $this->repository->GetReservationsMissingCheckInCheckOut($firstDate, $now, $this->searchUserId, $this->searchUserLevel, null, null, true);
        }

        else if (ServiceLocator::GetServer()->GetUserSession()->IsResourceAdmin || ServiceLocator::GetServer()->GetUserSession()->IsScheduleAdmin){
            $resourceIds = $this->GetUserAdminResources($user->UserId);

            if($resourceIds != null){

                $consolidated = $this->repository->GetReservationsMissingCheckInCheckOut($firstDate, $now, $this->searchUserId, $this->searchUserLevel, null, $resourceIds, true);
            }
        }

        $todays = [];
        $yesterdays = [];
        $thisWeeks = [];
        $previousWeeks = [];

        foreach ($consolidated as $reservation) {
            $start = $reservation->EndDate->ToTimezone($timezone);

            if ($start->DateEquals($today)) {
                $todays[] = $reservation;
            } elseif ($start->DateEquals($yesterday)) {
                $yesterdays[] = $reservation;
            } elseif ($start->GreaterThan($startOfPreviousWeek->AddDays(7))) {
                $thisWeeks[] = $reservation;
            } else {
                $previousWeeks[] = $reservation;
            }       //FALTA UM ELSE?
        }

        $checkinAdminOnly = Configuration::Instance()->GetSectionKey(ConfigSection::RESERVATION, ConfigKeys::RESERVATION_CHECKIN_ADMIN_ONLY, new BooleanConverter());
        $checkoutAdminOnly = Configuration::Instance()->GetSectionKey(ConfigSection::RESERVATION, ConfigKeys::RESERVATION_CHECKOUT_ADMIN_ONLY, new BooleanConverter());

        $allowCheckin = $user->IsAdmin || !$checkinAdminOnly;
        $allowCheckout = $user->IsAdmin || !$checkoutAdminOnly;

        $this->control->SetTotal(count($consolidated));
        $this->control->SetTimezone($timezone);
        $this->control->SetUserId($user->UserId);

        $this->control->SetAllowCheckin($allowCheckin);
        $this->control->SetAllowCheckout($allowCheckout);

        $this->control->BindToday($todays);
        $this->control->BindYesterday($yesterdays);
        $this->control->BindThisWeek($thisWeeks);
        $this->control->BindPreviousWeek($previousWeeks);
        }

    /**
     * Gets the resource ids that are under the responsability of the given resource user groups
     */
    private function GetUserAdminResources($userId){
        $resourceIds = [];

        $resourceRepo = new ResourceRepository();

        if (ServiceLocator::GetServer()->GetUserSession()->IsResourceAdmin){    
            $resourceIds = $resourceRepo->GetResourceAdminResourceIds($userId);
        }

        if (ServiceLocator::GetServer()->GetUserSession()->IsScheduleAdmin){
            $resourceIds = $resourceRepo->GetScheduleAdminResourceIds($userId, $resourceIds);
        }

        return $resourceIds;
    }
}