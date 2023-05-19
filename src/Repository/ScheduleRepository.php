<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Repository;

use App\Entity\Module;
use DateTimeInterface;
use Doctrine\Persistence\ManagerRegistry;
use Spyck\DashboardBundle\Entity\Schedule;

class ScheduleRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Schedule::class);
    }

    public function getScheduleDataByDate(DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('schedule')
            ->where('schedule.modules IS EMPTY')
            ->andWhere('JSON_LENGTH(schedule.hours) = 0 OR JSON_CONTAINS(schedule.hours, :hours) = TRUE')
            ->andWhere('JSON_LENGTH(schedule.days) = 0 OR JSON_CONTAINS(schedule.days, :days) = TRUE')
            ->andWhere('JSON_LENGTH(schedule.weeks) = 0 OR JSON_CONTAINS(schedule.weeks, :weeks) = TRUE')
            ->andWhere('JSON_LENGTH(schedule.weekdays) = 0 OR JSON_CONTAINS(schedule.weekdays, :weekdays) = TRUE')
            ->setParameter('hours', sprintf('%d', $date->format('G')))
            ->setParameter('days', sprintf('%d', $date->format('j')))
            ->setParameter('weeks', sprintf('%d', $date->format('W')))
            ->setParameter('weekdays', sprintf('%d', $date->format('N')))
            ->getQuery()
            ->getResult();
    }

    /**
     * Get schedules based on the last date that is parsed of the specific module.
     * Check if all related entity modules have the same date.
     */
    public function getScheduleDataByModule(Module $module): array
    {
        $date = $module->getDateEnd();

        if (null === $date) {
            return [];
        }

        return $this->createQueryBuilder('schedule')
            ->innerJoin('schedule.modules', 'module')
            ->where(':module MEMBER OF schedule.modules')
            ->groupBy('schedule')
            ->having('MIN(module.dateEnd) = MAX(module.dateEnd)')
            ->andHaving('JSON_LENGTH(schedule.hours) = 0 OR JSON_CONTAINS(schedule.hours, :hours) = TRUE')
            ->andHaving('JSON_LENGTH(schedule.days) = 0 OR JSON_CONTAINS(schedule.days, :days) = TRUE')
            ->andHaving('JSON_LENGTH(schedule.weeks) = 0 OR JSON_CONTAINS(schedule.weeks, :weeks) = TRUE')
            ->andHaving('JSON_LENGTH(schedule.weekdays) = 0 OR JSON_CONTAINS(schedule.weekdays, :weekdays) = TRUE')
            ->setParameter('module', $module)
            ->setParameter('hours', sprintf('%d', $date->format('G')))
            ->setParameter('days', sprintf('%d', $date->format('j')))
            ->setParameter('weeks', sprintf('%d', $date->format('W')))
            ->setParameter('weekdays', sprintf('%d', $date->format('N')))
            ->getQuery()
            ->getResult();
    }
}
