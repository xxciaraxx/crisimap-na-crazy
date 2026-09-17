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
        $query = Beneficiary::query()->active()->withIncludedAssistanceType()->with(['barangay:id,name', 'assistanceType:id,name,color', 'encoder:id,name', 'intake:id,beneficiary_name']);
        if ($user->role === 'social_officer') $query->where('encoded_by', $user->id);
        $query->when(filled($filters['barangay_id'] ?? null), fn ($q) => $q->where('barangay_id', $filters['barangay_id']))
            ->when(filled($filters['status'] ?? null), fn ($q) => $q->where('status', $filters['status']))
            ->when(filled($filters['search'] ?? null), fn ($q) => $q->where(fn ($inner) => $inner->where('name', 'like', '%'.trim($filters['search']).'%')->orWhere('reference_code', 'like', '%'.trim($filters['search']).'%')))
            ->when(filled($filters['from'] ?? null), fn ($q) => $q->whereDate('date_of_assistance', '>=', $filters['from']))
            ->when(filled($filters['to'] ?? null), fn ($q) => $q->whereDate('date_of_assistance', '<=', $filters['to']));
        $records = $query->whereNotNull('latitude')->whereNotNull('longitude')->orderByDesc('date_of_assistance')->limit(5000)->get(['id','name','reference_code','status','barangay_id','assistance_type_id','latitude','longitude','amount','date_of_assistance','encoded_by','general_intake_sheet_id'])->map(function (Beneficiary $b) {
            $style = $this->category($b->assistanceType?->name, $b->assistanceType?->color);
            $registeredBeneficiaryName = $b->intake?->beneficiary_name ?: $b->name;
            return ['id'=>$b->id,'latitude'=>$b->latitude,'longitude'=>$b->longitude,'status'=>$b->status,'date_availed'=>$b->date_of_assistance?->toDateString(),'barangay'=>$b->barangay?->name,'category'=>['id'=>$b->assistance_type_id,'name'=>$b->assistanceType->name] + $style,'details'=>['beneficiary'=>$registeredBeneficiaryName,'name'=>$registeredBeneficiaryName,'reference_code'=>$b->reference_code,'amount'=>$b->amount,'social_officer'=>$b->encoder?->name]];
        })->values();
        $this->dispatch('map-records-updated', beneficiaries: $records->all());
    }

    public function render() { return view('livewire.map-auto-loader'); }

    private function category(?string $name, ?string $color = null): array
    {
        foreach (['medical'=>['color'=>'#E53935','icon'=>'✚'],'burial'=>['color'=>'#424242','icon'=>'✦'],'food'=>['color'=>'#43A047','icon'=>'●'],'education'=>['color'=>'#1E88E5','icon'=>'◆'],'transportation'=>['color'=>'#FB8C00','icon'=>'➜'],'financial'=>['color'=>'#8E24AA','icon'=>'₱'],'cash'=>['color'=>'#8E24AA','icon'=>'₱'],'livelihood'=>['color'=>'#8E24AA','icon'=>'₱'],'construction'=>['color'=>'#FDB813','icon'=>'■']] as $key=>$style) if (str_contains(strtolower((string) $name), $key)) return ['color' => $color ?: $style['color'], 'icon' => $style['icon']];
        return ['color' => $color ?: '#00897B', 'icon'=>'•'];
    }
}
