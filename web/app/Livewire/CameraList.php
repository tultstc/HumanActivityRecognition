<?php

namespace App\Livewire;

use App\Models\Camera;
use App\Models\Group;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class CameraList extends Component
{
    use WithPagination;

    #[Url()]
    public $search = '';
    #[Url()]
    public $sort = 'name-asc';
    #[Url()]
    public $group = '';
    public $page = 1;

    protected $queryString = [
        'search' => ['except' => ''],
        'sort' => ['except' => 'desc'],
        'group' => ['group' => ''],
        'page' => ['except' => 1],
    ];

    public function setSort($sort)
    {
        if (in_array($sort, ['desc', 'asc', 'name-asc', 'name-desc'])) {
            $this->sort = $sort;
        }
    }

    #[On('search')]
    public function updateSearch($search)
    {
        $this->search = $search;
        $this->resetPage();
    }
    public function updateGroup($group)
    {
        $this->group = $group;
        $this->resetPage();
    }
    public function clearFilters()
    {
        $this->reset(['search', 'sort', 'group']);
        $this->resetPage();
    }

    #[Computed]
    public function cameras()
    {

        return Camera::published()
            ->when($this->search, function ($query) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($this->search) . '%']);
            })
            ->when($this->group, function ($query) {
                $query->whereHas('groups', function ($q) {
                    $q->where('id', $this->group);
                });
            })
            ->orderBy(
                $this->sort === 'name-asc' || $this->sort === 'name-desc' ? 'name' : 'updated_at',
                $this->sort === 'name-asc' || $this->sort === 'asc' ? 'asc' : 'desc'
            )
            ->paginate(8);
    }

    #[Computed]
    public function groups()
    {
        return Group::all();
    }

    public function render()
    {
        return view('livewire.camera-list');
    }
}