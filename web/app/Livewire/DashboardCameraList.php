<?php

namespace App\Livewire;

use App\Models\Area;
use App\Models\Camera;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Component;

class DashboardCameraList extends Component
{
    public $search = '';
    public $selectedLocationId = null;
    public $openNodes = [];
    public $layoutOptionsOpen = true;

    public function toggleLayoutOptions()
    {
        $this->layoutOptionsOpen = !$this->layoutOptionsOpen;
    }

    public function toggleNode($nodeKey)
    {
        $this->openNodes[$nodeKey] = !($this->openNodes[$nodeKey] ?? false);
    }

    public function changeLayout($columns)
    {
        $this->dispatch('changeLayout', columns: $columns);
        $this->updateCameras();
    }

    #[Computed()]
    public function cameras()
    {
        $cacheKey = 'dashboard_cameras_' . $this->search . '_' . $this->selectedLocationId;
        return Cache::remember($cacheKey, Carbon::now()->addMinutes(30), function () {
            return Camera::published()
                ->with(['group', 'position', 'cameraModels'])
                ->when($this->search, function ($query) {
                    $query->where('ten', 'like', '%' . $this->search . '%');
                })
                ->when($this->selectedLocationId, function ($query) {
                    $query->whereHas('position', function ($q) {
                        $q->where('khuvucid', $this->selectedLocationId);
                    });
                })
                ->take(Auth::user()->camera_layout)
                ->get();
        });
    }

    #[Computed()]
    public function locationGroups()
    {
        return Cache::remember('location_groups', Carbon::now()->addMinutes(30), function () {
            return Area::with(['positions' => function ($query) {
                $query->withCount('cameras');
            }])->get()->map(function ($area) {
                return [
                    'label' => $area->ten,
                    'id' => $area->id,
                    'children' => $area->positions->map(function ($positions) {
                        return [
                            'label' => $positions->ten . ' (' . $positions->cameras_count . ')',
                            'id' => $positions->id,
                            'camera_count' => $positions->cameras_count
                        ];
                    })->toArray()
                ];
            })->toArray();
        });
    }

    public function mount()
    {
        $this->initializeOpenStates($this->locationGroups);
    }

    private function initializeOpenStates($nodes, $parentKey = '')
    {
        foreach ($nodes as $index => $node) {
            $currentKey = $parentKey . $index;
            $this->openNodes[$currentKey] = true;

            if (isset($node['children'])) {
                $this->initializeOpenStates($node['children'], $currentKey . '-');
            }
        }
    }

    public function selectLocation($locationId)
    {
        $this->selectedLocationId = $locationId;
        $this->updateCameras();
    }

    public function updateCameras()
    {
        Cache::forget('dashboard_cameras_' . $this->search . '_' . $this->selectedLocationId);
        $this->cameras;
    }

    public function render()
    {
        return view('livewire.dashboard-camera-list');
    }
}