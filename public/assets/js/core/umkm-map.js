/** umkm-map.js - core reusable script */
window.UMKM = window.UMKM || {};
(function(){
  const province=document.querySelector('[name="province_region_id"]');
  const city=document.querySelector('[name="city_region_id"]');
  const district=document.querySelector('[name="district_region_id"]');
  const village=document.querySelector('[name="village_region_id"]');
  async function load(level,parent,target){ if(!target) return; target.innerHTML='<option value="">Pilih</option>'; if(!parent) return; const res=await fetch(`/api/internal/regions?level=${level}&parent_id=${parent}`,{credentials:'include'}); const json=await res.json(); (json.data||[]).forEach(r=>{const o=document.createElement('option');o.value=r.id;o.textContent=r.region_name;target.appendChild(o);}); }
  province?.addEventListener('change',e=>{if(city) city.value=''; if(district) district.innerHTML='<option value="">Pilih</option>'; if(village) village.innerHTML='<option value="">Pilih</option>'; load('city_regency',e.target.value,city);});
  city?.addEventListener('change',e=>{if(district) district.value=''; if(village) village.innerHTML='<option value="">Pilih</option>'; load('district',e.target.value,district);});
  district?.addEventListener('change',e=>{if(village) village.value=''; load('village',e.target.value,village);});
})();
