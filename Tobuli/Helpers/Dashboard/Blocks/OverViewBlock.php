<?php namespace Tobuli\Helpers\Dashboard\Blocks;

use Carbon\Carbon;
use Tobuli\Lookups\Tables\DevicesIdleLookupTable;
use Tobuli\Lookups\Tables\DevicesInactiveLookupTable;
use Tobuli\Lookups\Tables\DevicesMoveLookupTable;
use Tobuli\Lookups\Tables\DevicesNeverConnectedLookupTable;
use Tobuli\Lookups\Tables\DevicesOfflineLookupTable;
use Tobuli\Lookups\Tables\DevicesParkLookupTable;
use Tobuli\Lookups\Tables\DevicesStopLookupTable;
use Tobuli\Lookups\Tables\ObjectListLookupTable;

class OverviewBlock extends Block
{
    protected function getName()
    {
        return 'over_view';
    }

    protected function getContent()
    {
        $devices = $this->user->devices();

        return [
            'statuses' => $this->getStatuses($devices),
            'total'    => (clone $devices)->count()
        ];
    }

    protected function getStatuses($devices)
    {

        return [
            [
                'label' => trans('front.move'),
                'data' => (clone $devices)->move()->count(),
                'color' => '#388E3C',
                'url' => DevicesMoveLookupTable::route('index')
            ],
            [
                'label' => trans('front.idle'),
                'data' => (clone $devices)->idle()->count(),
                'color' => '#0288D1',
                'url' => DevicesIdleLookupTable::route('index')
            ],
            [
                'label' => trans('front.stop'),
                'data' => (clone $devices)->park()->count(),
                'color' => '#EDC100',
                'url' => DevicesParkLookupTable::route('index')
            ],
            [
                'label' => trans('front.offline'),
                'data' => (clone $devices)->offline()->count(),
                'color' => '#E64A19',
                'url'  => DevicesOfflineLookupTable::route('index')
            ],
           
            [
                'label' => trans('front.never_connected'),
                'data' => (clone $devices)->neverConnected()->count(),
                'color' => '#F57C00',
                'url' => DevicesNeverConnectedLookupTable::route('index')
            ],
            [
                'label' => trans('front.inactive'),
                'data' => (clone $devices)->inactive()->count(),
                'color' => '#D7DBDD',
                'url' => DevicesInactiveLookupTable::route('index')
            ],
            [
                'label' => trans('front.total'),
                'data' => (clone $devices)->count(),
                'color' => '#424242',
                'url' => ObjectListLookupTable::route('index')
            ]
        ];
    }
}
