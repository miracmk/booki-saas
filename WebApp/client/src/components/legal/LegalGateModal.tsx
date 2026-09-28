import React, { useState, useRef, useEffect } from "react";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { Label } from "@/components/ui/label";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Badge } from "@/components/ui/badge";
import { DigitalSignaturePad } from "./DigitalSignaturePad";
import { toast } from "sonner";
import {
  BookingContextData,
  BiometricMetadata,
  DocumentType,
  SignedContractResponse,
} from "@shared/legalTypes";
import {
  ShieldCheck,
  FileText,
  AlertTriangle,
  Lock,
  ChevronDown,
  CheckCircle2,
  MailCheck,
} from "lucide-react";

interface LegalGateModalProps {
  isOpen: boolean;
  onClose: () => void;
  bookingContext: BookingContextData;
  onAllContractsSigned: (results: SignedContractResponse[]) => void;
}

export const LegalGateModal: React.FC<LegalGateModalProps> = ({
  isOpen,
  onClose,
  bookingContext,
  onAllContractsSigned,
}) => {
  const [activeTab, setActiveTab] = useState<string>("mss");
  const [scrollCompletedMap, setScrollCompletedMap] = useState<Record<string, boolean>>({
    mss: false,
    kvkk: false,
    consent: false,
  });

  // Torba Rıza Yasağı: Bağımsız Onay Checkbox'ları (KVKK Kurul Kararı 2020/173)
  const [acceptMss, setAcceptMss] = useState(false);
  const [readKvkk, setReadKvkk] = useState(false);
  const [explicitConsent, setExplicitConsent] = useState(false); // Bağımsız Opt-in
  const [marketingConsent, setMarketingConsent] = useState(false); // Bağımsız Opt-in

  // Signature data
  const [signatureData, setSignatureData] = useState<{
    base64Png: string;
    svgString: string;
    biometrics: BiometricMetadata;
  } | null>(null);

  const [isSubmitting, setIsSubmitting] = useState(false);

  // Contract Markdown cache
  const [contractsContent, setContractsContent] = useState<{
    mss: string;
    kvkk: string;
    consent: string;
  }>({ mss: "", kvkk: "", consent: "" });

  useEffect(() => {
    if (isOpen) {
      loadContractTexts();
      setScrollCompletedMap({ mss: false, kvkk: false, consent: false });
      setAcceptMss(false);
      setReadKvkk(false);
      setExplicitConsent(false);
      setMarketingConsent(false);
      setSignatureData(null);
    }
  }, [isOpen]);

  const loadContractTexts = async () => {
    try {
      const res = await fetch("/api/legal/requirements", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          sectorFamily: "beauty_wellness",
          flowContext: "CHECKOUT",
          context: bookingContext,
        }),
      });
      const data = await res.json();
      if (data.renderedContracts) {
        setContractsContent({
          mss: data.renderedContracts.MESAFELI_SATIS_SOZLESMESI || "Mesafeli Satış Sözleşmesi yükleniyor...",
          kvkk: data.renderedContracts.KVKK_AYDINLATMA || "KVKK Aydınlatma Metni yükleniyor...",
          consent: "ÖZEL NİTELİKLİ VERİ AÇIK RIZA METNİ\n(6698 Sayılı KVKK Madde 6 Uyarınca Bağımsız Rıza Formu)\n\nRandevu ve hizmet esnasında tarafımca beyan edilen sağlık ve kişisel verilerin işlenmesine kendi hür irademle açık rıza veriyorum.",
        });
      }
    } catch {
      // fallback
    }
  };

  // Scroll to bottom tracking
  const handleScroll = (tabKey: string, e: React.UIEvent<HTMLDivElement>) => {
    const { scrollTop, scrollHeight, clientHeight } = e.currentTarget;
    if (scrollTop + clientHeight >= scrollHeight - 30) {
      if (!scrollCompletedMap[tabKey]) {
        setScrollCompletedMap((prev) => ({ ...prev, [tabKey]: true }));
        toast.success("Metnin sonuna ulaştınız. Onay kutucuğu aktifleşti.");
      }
    }
  };

  const handleSealAndSubmit = async () => {
    if (!readKvkk) {
      toast.error("Lütfen KVKK Aydınlatma Metnini okuduğunuzu teyit ediniz.");
      return;
    }
    if (!acceptMss) {
      toast.error("Hizmet ifası için Mesafeli Satış Sözleşmesi'nin kabulü zorunludur.");
      return;
    }
    if (!signatureData?.base64Png) {
      toast.error("Lütfen imza kutusuna biyometrik imzanızı atınız.");
      return;
    }

    setIsSubmitting(true);
    try {
      // 1. Sign MSS
      const signRes = await fetch("/api/legal/sign", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          templateId: "MESAFELI_SATIS_SOZLESMESI",
          docType: "MESAFELI_SATIS_SOZLESMESI",
          tenantId: bookingContext.tenantId,
          bookingId: bookingContext.bookingId,
          customerId: bookingContext.customerId,
          signerName: bookingContext.customerFullName || bookingContext.customerName,
          signerNationalId: bookingContext.customerNationalId,
          signerPhone: bookingContext.customerPhone,
          signerEmail: bookingContext.customerEmail,
          signatureType: "CANVAS_BIOMETRIC",
          signatureCanvasBase64: signatureData.base64Png,
          signatureCanvasSvg: signatureData.svgString,
          biometricMetadata: signatureData.biometrics,
          acceptedTerms: true,
          scrollCompleted: true,
          context: bookingContext,
        }),
      });

      const signedJson = await signRes.json();
      if (!signRes.ok || !signedJson.success) {
        throw new Error(signedJson.error || "Sözleşme imzalanamadı");
      }

      toast.success("Sözleşme SHA-256 ile mühürlendi ve nüshası e-posta/SMS ile tarafınıza iletildi!");
      onAllContractsSigned([signedJson]);
      onClose();
    } catch (err: any) {
      toast.error("Hata: " + err.message);
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
      <DialogContent className="max-w-2xl max-h-[94vh] flex flex-col p-0 overflow-hidden bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800">
        <DialogHeader className="p-5 pb-3 border-b border-slate-100 dark:border-slate-800">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2">
              <ShieldCheck className="w-5 h-5 text-emerald-600" />
              <DialogTitle className="text-base font-semibold text-slate-900 dark:text-slate-100">
                Yasal Onay Kapısı (LegalGate)
              </DialogTitle>
            </div>
            <Badge variant="outline" className="text-[10px] bg-emerald-50 text-emerald-800 border-emerald-300">
              TBK 20-25 & KVKK 2020/173 Uyumlu
            </Badge>
          </div>
          <DialogDescription className="text-xs text-slate-500">
            Haksız şart ve torba rıza içermeyen, bağımsız onaylı dijital sözleşme protokolü.
          </DialogDescription>
        </DialogHeader>

        <div className="flex-1 overflow-y-auto p-5 space-y-4">
          <Tabs value={activeTab} onValueChange={setActiveTab}>
            <TabsList className="grid grid-cols-3 w-full h-8 text-xs">
              <TabsTrigger value="mss" className="text-xs">
                1. Mesafeli Satış
              </TabsTrigger>
              <TabsTrigger value="kvkk" className="text-xs">
                2. KVKK Aydınlatma
              </TabsTrigger>
              <TabsTrigger value="consent" className="text-xs">
                3. Açık Rıza (Opt-in)
              </TabsTrigger>
            </TabsList>

            {/* TAB 1: MSS */}
            <TabsContent value="mss" className="space-y-3 mt-3">
              {!scrollCompletedMap.mss && (
                <div className="flex items-center gap-2 p-2 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 rounded text-amber-800 text-[11px]">
                  <ChevronDown className="w-3.5 h-3.5 animate-bounce" />
                  <span>Sözleşme şartlarını incelemek için lütfen metni sonuna kadar kaydırınız.</span>
                </div>
              )}
              <div
                onScroll={(e) => handleScroll("mss", e)}
                className="h-40 overflow-y-auto p-3.5 bg-slate-50 dark:bg-slate-950 rounded border border-slate-200 dark:border-slate-800 text-xs text-slate-700 dark:text-slate-300 whitespace-pre-wrap select-none"
              >
                {contractsContent.mss}
              </div>
              <div className="flex items-start gap-2 p-2.5 bg-slate-50 dark:bg-slate-900 rounded border border-slate-200 dark:border-slate-800">
                <Checkbox
                  id="chk-mss"
                  checked={acceptMss}
                  disabled={!scrollCompletedMap.mss}
                  onCheckedChange={(c) => setAcceptMss(Boolean(c))}
                />
                <Label
                  htmlFor="chk-mss"
                  className={`text-xs select-none ${
                    !scrollCompletedMap.mss ? "text-slate-400" : "text-slate-700 dark:text-slate-300 font-medium"
                  }`}
                >
                  Mesafeli Satış Sözleşmesi'ni okudum, anladım ve şartlarını kabul ediyorum.
                </Label>
              </div>
            </TabsContent>

            {/* TAB 2: KVKK Aydınlatma */}
            <TabsContent value="kvkk" className="space-y-3 mt-3">
              <div
                onScroll={(e) => handleScroll("kvkk", e)}
                className="h-40 overflow-y-auto p-3.5 bg-slate-50 dark:bg-slate-950 rounded border border-slate-200 dark:border-slate-800 text-xs text-slate-700 dark:text-slate-300 whitespace-pre-wrap select-none"
              >
                {contractsContent.kvkk}
              </div>
              <div className="flex items-start gap-2 p-2.5 bg-slate-50 dark:bg-slate-900 rounded border border-slate-200 dark:border-slate-800">
                <Checkbox
                  id="chk-kvkk"
                  checked={readKvkk}
                  onCheckedChange={(c) => setReadKvkk(Boolean(c))}
                />
                <Label htmlFor="chk-kvkk" className="text-xs text-slate-700 dark:text-slate-300 font-medium select-none">
                  KVKK 10. Maddesi Kapsamındaki Aydınlatma Metnini okudum ve haklarım konusunda bilgilendirildim.
                </Label>
              </div>
            </TabsContent>

            {/* TAB 3: Bağımsız Açık Rıza ve Ticari İleti (Torba Rıza Değil, Serbest Opt-in) */}
            <TabsContent value="consent" className="space-y-3 mt-3">
              <div className="p-3 bg-sky-50/60 dark:bg-sky-950/20 border border-sky-200 dark:border-sky-800 rounded text-xs text-slate-700 dark:text-slate-300 space-y-1">
                <p className="font-semibold text-sky-900 dark:text-sky-300">Torba Rıza Yasağı Uyarınca:</p>
                <p className="text-[11px] text-slate-500">
                  Hizmet almanız aşağıdaki izinlerin verilmesi şartına bağlanamaz. Tercihinize göre işaretleyebilirsiniz.
                </p>
              </div>

              <div className="space-y-2 pt-1">
                <div className="flex items-start gap-2 p-2.5 bg-slate-50 dark:bg-slate-900 rounded border border-slate-200 dark:border-slate-800">
                  <Checkbox
                    id="chk-consent"
                    checked={explicitConsent}
                    onCheckedChange={(c) => setExplicitConsent(Boolean(c))}
                  />
                  <Label htmlFor="chk-consent" className="text-xs text-slate-700 dark:text-slate-300 select-none">
                    Özel nitelikli sağlık ve kişisel verilerimin randevu takibi amacıyla işlenmesine <strong>açık rıza</strong> gösteriyorum (İsteğe bağlı).
                  </Label>
                </div>

                <div className="flex items-start gap-2 p-2.5 bg-slate-50 dark:bg-slate-900 rounded border border-slate-200 dark:border-slate-800">
                  <Checkbox
                    id="chk-mkt"
                    checked={marketingConsent}
                    onCheckedChange={(c) => setMarketingConsent(Boolean(c))}
                  />
                  <Label htmlFor="chk-mkt" className="text-xs text-slate-700 dark:text-slate-300 select-none">
                    İşletmenin kampanya ve indirimlerinden haberdar olmak için SMS/E-posta almayı kabul ediyorum (İsteğe bağlı).
                  </Label>
                </div>
              </div>
            </TabsContent>
          </Tabs>

          {/* Biyometrik İmza Pad'i */}
          <div className="pt-2 border-t border-slate-100 dark:border-slate-800">
            <DigitalSignaturePad
              onSignatureComplete={setSignatureData}
              disabled={!acceptMss || !readKvkk}
            />
          </div>
        </div>

        <DialogFooter className="p-4 bg-slate-50 dark:bg-slate-950 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between sm:justify-between">
          <Button variant="outline" size="sm" onClick={onClose} disabled={isSubmitting}>
            İptal
          </Button>
          <Button
            size="sm"
            onClick={handleSealAndSubmit}
            disabled={!acceptMss || !readKvkk || !signatureData?.base64Png || isSubmitting}
            className="gap-2 bg-emerald-600 hover:bg-emerald-700 text-white"
          >
            <CheckCircle2 className="w-4 h-4" />
            {isSubmitting ? "Mühürleniyor & İletiliyor..." : "Sözleşmeyi İmzala ve Onayla"}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
};
