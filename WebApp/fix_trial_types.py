from pathlib import Path
p=Path('/home/ubuntu/ki-reservation-website/upgrade_pricing.py')
s=p.read_text()
s=s.replace('const [trialPlan, setTrialPlan] = useState("Professional");','const [trialPlan, setTrialPlan] = useState<"Enterprise" | "Professional" | "Starter">("Professional");')
s=s.replace('plan: String(formData.get("plan") || trialPlan),','plan: trialPlan,')
s=s.replace('onChange={event => setTrialPlan(event.target.value)}','onChange={event => setTrialPlan(event.target.value as "Enterprise" | "Professional" | "Starter")}')
p.write_text(s)

# Make the complete trial payload compatible with the existing email helper.
r=Path('/home/ubuntu/ki-reservation-website/server/routers.ts')
rs=r.read_text()
old='''        phone: z.string().trim().regex(/^[0-9+ ()-]{10,20}$/),\n        businessType: z.string().trim().min(2).max(80),\n        plan: z.enum(["Enterprise", "Professional", "Starter"]),'''
new='''        phone: z.string().trim().regex(/^[0-9+ ()-]{10,20}$/),\n        preferredContactTime: z.string().trim().default("14 günlük deneme kaydı"),\n        businessType: z.string().trim().min(2).max(80),\n        plan: z.enum(["Enterprise", "Professional", "Starter"]),'''
if old not in rs: raise SystemExit('trial schema not found')
rs=rs.replace(old,new,1)
r.write_text(rs)
