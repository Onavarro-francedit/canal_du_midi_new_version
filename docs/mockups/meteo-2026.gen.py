import json,sys
SP=sys.argv[1]
st=json.load(open(f"{SP}/meteo8.json"))
MO=['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre']
NAV=['basse','basse','basse puis moyenne (17 mars)','moyenne','haute','haute','haute','haute','haute','moyenne','basse','basse']
NAVH={'basse':'écluses à la demande, 8 h 30 – 16 h 30','moyenne':'écluses de 8 h à 19 h','haute':'écluses de 8 h à 19 h 30','basse puis moyenne (17 mars)':'à la demande jusqu’au 16, puis écluses de 8 h à 19 h'}
f1=lambda v:str(round(v,1)).replace('.',',').replace(',0','') if round(v,1)!=int(v) or True else str(int(v))
def f1(v):
    v=round(v,1); return (str(int(v)) if v==int(v) else str(v).replace('.',','))
fd=lambda v:str(int(round(v)))
def rng(a,b,f): return f(a) if f(a)==f(b) else f(a)+' à '+f(b)
def col(k,i): return [s[k][i] for s in st]
def kind(i):
    if NAV[i].startswith('basse'): return 'off'
    h=max(col('hot',i)); t=min(col('tmax',i))
    if h>15: return 'vhot'
    if h>8: return 'hot'
    if t<20: return 'mild'
    return 'ideal'
months=[]
for i in range(12):
    tx=col('tmax',i); h=col('hot',i); r=col('rain',i)
    hot_s = 'jamais 30 °C en moyenne' if max(h)<0.5 else f"{rng(min(h),max(h),fd)} jours à 30 °C ou plus"
    text=(f"L’après-midi, {rng(min(tx),max(tx),fd)} °C selon l’étape ; {hot_s} ; {rng(min(r),max(r),fd)} jours de pluie. "
          f"Navigation : {NAV[i]} saison, {NAVH[NAV[i]]}.")
    if max(h)>8:
        hi=max(st,key=lambda s:s['tmax'][i]); lo=min(st,key=lambda s:s['tmax'][i])
        text+=f" Le plus chaud : {hi['name']} ({f1(hi['tmax'][i])} °C, {fd(hi['hot'][i])} jours à 30 °C+) ; le plus frais : {lo['name']} ({f1(lo['tmax'][i])} °C)."
    months.append({'kind':kind(i),'tmax':[min(tx),max(tx)],'hot':[min(h),max(h)],'text':text})
ideal=[i for i in range(12) if months[i]['kind']=='ideal']
lab=lambda idx:', '.join(MO[i] for i in idx[:-1])+' et '+MO[idx[-1]] if len(idx)>1 else MO[idx[0]]
legend={'ideal':'≥ 20 °C, au plus 8 j à 30 °C+','mild':'moins de 20 °C l’après-midi','hot':'9 à 15 j à 30 °C+','vhot':'plus de 15 j à 30 °C+','off':'basse saison de navigation'}
def season(name,idx):
    tx=[v for i in idx for v in col('tmax',i)]
    hot=[sum(s['hot'][i] for i in idx) for s in st]
    rain=[sum(s['rain'][i] for i in idx) for s in st]
    navs=[]
    for i in idx:
        for part in NAV[i].replace(' (17 mars)','').split(' puis '):
            if not navs or navs[-1]!=part: navs.append(part)
    return {'name':name,'months':', '.join(MO[i] for i in idx),'rows':[
        ['L’après-midi',f"{rng(min(tx),max(tx),fd)} °C selon le mois et l’étape"],
        ['Jours à 30 °C ou plus', 'aucun' if max(hot)<0.5 else f"{rng(min(hot),max(hot),fd)} sur la saison selon l’étape"],
        ['Jours de pluie',f"{rng(min(rain),max(rain),fd)} sur la saison"],
        ['Navigation',' puis '.join(navs)+' saison']]}
seasons=[season('Printemps',[2,3,4]),season('Été',[5,6,7]),season('Automne',[8,9,10]),season('Hiver',[11,0,1])]
jul=col('hot',6); aug=col('hot',7)
hot_hi=max(st,key=lambda s:s['hot'][6]+s['hot'][7]); hot_lo=min(st,key=lambda s:s['hot'][6]+s['hot'][7])
ways=[
 {'icon':'🚤','title':'En bateau','best':lab(ideal).capitalize(),'points':[
   'Haute saison de navigation de mai à septembre (écluses de 8 h à 19 h 30), moyenne saison en avril et octobre (8 h – 19 h).',
   f"En juillet-août, {rng(min(jul),max(jul),fd)} jours à 30 °C ou plus en juillet selon l’étape : naviguez tôt le matin.",
   'Du 1er novembre au 16 mars, écluses à la demande de 8 h 30 à 16 h 30 ; navigation fermée les 1er janvier, 1er mai, 11 novembre et 25 décembre.']},
 {'icon':'🚲','title':'À vélo','best':'Avril, '+lab(ideal),'points':[
   f"En avril, {rng(min(col('tmax',3)),max(col('tmax',3)),fd)} °C l’après-midi et presque jamais 30 °C : idéal pour enchaîner les étapes.",
   f"En été, le plus chaud : vers {hot_hi['name']} ({fd(hot_hi['hot'][6]+hot_hi['hot'][7])} jours à 30 °C+ en juillet-août) ; le plus frais : vers l’{hot_lo['name']} ({fd(hot_lo['hot'][6]+hot_lo['hot'][7])}).",
   'En juillet-août, roulez le matin et emportez de l’eau.']},
 {'icon':'🥾','title':'À pied','best':'Toute l’année hors juillet-août','points':[
   f"En hiver, {rng(min(col('tmax',0)),max(col('tmax',0)),fd)} °C l’après-midi en janvier, plus doux vers la Méditerranée.",
   f"Le plus pluvieux : novembre, {rng(min(col('rain',10)),max(col('rain',10)),fd)} jours de pluie selon l’étape (plus côté Lauragais).",
   'En juillet-août, marchez tôt et évitez les heures chaudes.']},
]
best_tx=[(MO[i],min(col('tmax',i)),max(col('tmax',i))) for i in ideal]
faq=[
 {'q':'Quelle est la meilleure période pour faire le Canal du Midi ?','a':f"{lab(ideal).capitalize()} : l’après-midi, "+' ; '.join(f"{rng(a,b,fd)} °C en {m}" for m,a,b in best_tx)+f" selon l’étape, au plus {fd(max(max(col('hot',i)) for i in ideal))} jours à 30 °C ou plus, et le canal est ouvert à la navigation. Juillet et août sont les mois les plus chauds."},
 {'q':'Quel temps fait-il sur le Canal du Midi en été ?','a':f"En juillet, {rng(min(col('tmax',6)),max(col('tmax',6)),f1)} °C de maximale moyenne et {rng(min(jul),max(jul),fd)} jours à 30 °C ou plus selon l’étape ; en août, {rng(min(col('tmax',7)),max(col('tmax',7)),f1)} °C et {rng(min(aug),max(aug),fd)} jours. Le plus chaud vers {hot_hi['name']}, le plus frais vers l’{hot_lo['name']}."},
 {'q':'Quel temps fait-il sur le Canal du Midi en hiver ?','a':f"En janvier, {rng(min(col('tmin',0)),max(col('tmin',0)),f1)} °C le matin et {rng(min(col('tmax',0)),max(col('tmax',0)),f1)} °C l’après-midi selon l’étape, plus doux vers la Méditerranée."},
 {'q':'Le Canal du Midi est-il fermé en hiver ?','a':'Non, mais la navigation est en basse saison du 1er novembre au 16 mars : de 8 h 30 à 16 h 30, à la demande. Elle est fermée sur tout le canal le 1er janvier, le 1er mai, le 11 novembre et le 25 décembre. Les fermetures pour travaux (chômages) sont annoncées chaque année par Voies navigables de France.'},
]
data={'stations':[{'name':s['name'],'tmax':s['tmax'],'tmin':s['tmin'],'hot':s['hot'],'rain':s['rain']} for s in st],'months':months,'legend':legend,'ways':ways,'seasons':seasons,'faq':faq}
tpl=open(sys.argv[2]).read()
open(sys.argv[3],'w').write(tpl.replace('__DATA__',json.dumps(data,ensure_ascii=False)))
print('meses:',[m['kind'] for m in months]); print(faq[0]['a']); print(ways[1]['points'][1])
