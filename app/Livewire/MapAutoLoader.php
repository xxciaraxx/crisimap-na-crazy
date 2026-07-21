<?php

namespace App\Livewire;

use App\Models\Beneficiary;
use Livewire\Attributes\On;
use Livewire\Component;

class MapAutoLoader extends Component
{
    #[On('map-filters-changed')]
    public function load(array $filters = []): void
    {
        $user = auth()->user();
        $query = Beneficiary::query()->active()->with(['barangay:id,name', 'assistanceType:id,name', 'caseRecord:id,case_number,encoded_by', 'encoder:id,name']);
        if ($user->role === 'social_officer') $query->whereHas('caseRecord', fn ($cases) => $cases->where('encoded_by', $user->id));
        $query->when(filled($filters['barangay_id'] ?? null), fn ($q) => $q->where('barangay_id', $filters['barangay_id']))
            ->when(filled($filters['status'] ?? null), fn ($q) => $q->where('status', $filters['status']))
            ->when(filled($filters['search'] ?? null), fn ($q) => $q->where(fn ($inner) => $inner->where('name', 'like', '%'.trim($filters['search']).'%')->orWhere('reference_code', 'like', '%'.trim($filters['search']).'%')))
            ->when(filled($filters['from'] ?? null), fn ($q) => $q->whereDate('date_of_assistance', '>=', $filters['from']))
            ->when(filled($filters['to'] ?? null), fn ($q) => $q->whereDate('date_of_assistance', '<=', $filters['to']));
        $records = $query->whereNotNull('latitude')->whereNotNull('longitude')->orderByDesc('date_of_assistance')->limit(5000)->get(['id','name','reference_code','status','barangay_id','assistance_type_id','case_record_id','latitude','longitude','amount','date_of_assistance','encoded_by'])->map(function (Beneficiary $b) {
            $style = $this->category($b->assistanceType?->name);
            return ['id'=>$b->id,'latitude'=>$b->latitude,'longitude'=>$b->longitude,'status'=>$b->status,'date_availed'=>$b->date_of_assistance?->toDateString(),'barangay'=>$b->barangay?->name,'category'=>['id'=>$b->assistance_type_id ?? 'other','name'=>$b->assistanceType?->name ?? 'Other / Miscellaneous'] + $style,'details'=>['name'=>$b->name,'reference_code'=>$b->reference_code,'amount'=>$b->amount,'case_record_id'=>$b->case_record_id,'case_number'=>$b->caseRecord?->case_number,'social_officer'=>$b->encoder?->name]];
        })->values();
        $this->dispatch('map-records-updated', beneficiaries: $records->all());
    }

    public function render() { return view('livewire.map-auto-loader'); }

    private function category(?string $name): array
    {
        foreach (['medical'=>['color'=>'#E53935','icon'=>'✚'],'burial'=>['color'=>'#424242','icon'=>'✦'],'food'=>['color'=>'#43A047','icon'=>'●'],'education'=>['color'=>'#1E88E5','icon'=>'◆'],'transportation'=>['color'=>'#FB8C00','icon'=>'➜'],'financial'=>['color'=>'#8E24AA','icon'=>'₱'],'cash'=>['color'=>'#8E24AA','icon'=>'₱'],'livelihood'=>['color'=>'#8E24AA','icon'=>'₱'],'construction'=>['color'=>'#FDB813','icon'=>'■']] as $key=>$style) if (str_contains(strtolower((string) $name), $key)) return $style;
        return ['color'=>'#FDB813','icon'=>'•'];
    }
}
