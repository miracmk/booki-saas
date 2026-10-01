import React, { useEffect, useState } from "react";
import { useRoute, Link } from "wouter";
import { ShieldCheck, AlertCircle, FileText, CheckCircle2, Clock, MapPin, Download, ArrowLeft } from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { ContractVerificationResponse } from "@shared/legalTypes";

export const ContractVerificationPage: React.FC = () => {
  const [, params] = useRoute("/v/:hash");
  const hash = params?.hash;

  const [loading, setLoading] = useState(true);
  const [data, setData] = useState<ContractVerificationResponse | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!hash) {
      setError("Doğrulama hash kodu belirtilmedi.");
      setLoading(false);
      return;
    }

    fetch(`/api/legal/verify/${hash}`)
      .then(async (res) => {
        if (!res.ok) {
          const errJson = await res.json().catch(() => ({}));
          throw new Error(errJson.message || "Sözleşme doğrulanamadı.");
        }
        return res.json();
      })
      .then((resData: ContractVerificationResponse) => {
        setData(resData);
        setLoading(false);
      })
      .catch((err: any) => {
        setError(err.message || "Doğrulama servisine ulaşılamadı.");
        setLoading(false);
      });
  }, [hash]);

  return (
    <div className="min-h-screen bg-slate-50 py-12 px-4 sm:px-6 lg:px-8">
      <div className="max-w-3xl mx-auto">
        <div className="mb-6">
          <Link href="/">
            <Button variant="ghost" size="sm" className="gap-2 text-slate-600 hover:text-slate-900">
              <ArrowLeft className="w-4 h-4" /> Ana Sayfaya Dön
            </Button>
          </Link>
        </div>

        {loading ? (
          <Card className="shadow-lg border-slate-200">
            <CardContent className="py-16 text-center">
              <div className="inline-block animate-spin rounded-full h-10 w-10 border-4 border-emerald-500 border-t-transparent mb-4" />
              <p className="text-slate-600 font-medium">Sözleşme SHA-256 ve RFC 3161 Zaman Damgası Doğrulanıyor...</p>
            </CardContent>
          </Card>
        ) : error || !data || !data.isValid ? (
          <Card className="shadow-lg border-red-200">
            <CardHeader className="text-center pb-4">
              <div className="mx-auto w-14 h-14 bg-red-100 rounded-full flex items-center justify-center mb-3">
                <AlertCircle className="w-8 h-8 text-red-600" />
              </div>
              <CardTitle className="text-2xl text-red-700">Doğrulama Başarısız</CardTitle>
              <CardDescription className="text-slate-600">
                {error || "Bu hash koduna ait geçerli ve mühürlü bir sözleşme kaydı bulunamadı."}
              </CardDescription>
            </CardHeader>
          </Card>
        ) : (
          <Card className="shadow-xl border-emerald-200 bg-white overflow-hidden">
            <div className="bg-gradient-to-r from-emerald-600 to-teal-700 p-6 text-white text-center">
              <div className="mx-auto w-16 h-16 bg-white/20 backdrop-blur-md rounded-full flex items-center justify-center mb-3">
                <ShieldCheck className="w-10 h-10 text-white" />
              </div>
              <h1 className="text-2xl font-bold tracking-tight">Resmi Dijital Sözleşme Doğrulandı</h1>
              <p className="text-emerald-100 text-sm mt-1">
                HMK m. 199/202, 5070 Sayılı E-İmza Kanunu ve RFC 3161 Zaman Damgalı Orijinal Belge
              </p>
            </div>

            <CardContent className="p-6 sm:p-8 space-y-6">
              <div className="flex flex-wrap items-center justify-between gap-2 pb-4 border-b border-slate-100">
                <div>
                  <h2 className="text-lg font-semibold text-slate-900">{data.title}</h2>
                  <p className="text-xs text-slate-500 uppercase tracking-wider font-mono">{data.docType}</p>
                </div>
                <Badge className="bg-emerald-100 text-emerald-800 hover:bg-emerald-100 border border-emerald-300 gap-1.5 py-1 px-3">
                  <CheckCircle2 className="w-4 h-4 text-emerald-600" /> Değiştirilemez Mühürlü
                </Badge>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div className="bg-slate-50 p-3.5 rounded-lg border border-slate-100">
                  <span className="text-xs text-slate-400 font-medium block mb-1">Hizmet Sağlayıcı / Tesis</span>
                  <p className="font-semibold text-slate-800">{data.tenantName}</p>
                </div>
                <div className="bg-slate-50 p-3.5 rounded-lg border border-slate-100">
                  <span className="text-xs text-slate-400 font-medium block mb-1">İmzalayan / Müşteri</span>
                  <p className="font-semibold text-slate-800">{data.signerName}</p>
                </div>
                <div className="bg-slate-50 p-3.5 rounded-lg border border-slate-100">
                  <span className="text-xs text-slate-400 font-medium block mb-1">İmza Zamanı (UTC)</span>
                  <div className="flex items-center gap-1.5 text-slate-700 font-mono text-xs">
                    <Clock className="w-3.5 h-3.5 text-slate-400" />
                    {new Date(data.signedAtUtc).toLocaleString("tr-TR")}
                  </div>
                </div>
                <div className="bg-slate-50 p-3.5 rounded-lg border border-slate-100">
                  <span className="text-xs text-slate-400 font-medium block mb-1">İmza Türü & Güvenlik</span>
                  <p className="text-xs font-medium text-slate-700">
                    {data.hasOtpVerification ? "SMS OTP (2FA) + Biyometrik Kanvas" : "Biyometrik Kanvas Dijital İmza"}
                  </p>
                </div>
              </div>

              {/* Kriptografik Özet */}
              <div className="bg-slate-900 text-slate-200 p-4 rounded-lg font-mono text-xs space-y-2">
                <div className="text-slate-400 text-[11px] uppercase tracking-wider font-semibold">
                  Kriptografik SHA-256 Özeti:
                </div>
                <div className="break-all text-emerald-400 bg-slate-950/60 p-2 rounded border border-slate-800">
                  {data.sha256Hash}
                </div>
                {data.timestampToken && (
                  <div>
                    <span className="text-slate-400 text-[11px]">RFC 3161 Damgası: </span>
                    <span className="text-slate-300 break-all">{data.timestampToken}</span>
                  </div>
                )}
                {data.blockHash && (
                  <div>
                    <span className="text-slate-400 text-[11px]">Blok Zincir Hash'i: </span>
                    <span className="text-slate-400 break-all">{data.blockHash.slice(0, 32)}...</span>
                  </div>
                )}
              </div>

              {/* Hukuki Uyarı */}
              <div className="bg-amber-50 border border-amber-200 p-3.5 rounded-lg text-xs text-amber-900 leading-relaxed">
                {data.legalNotice}
              </div>
            </CardContent>
          </Card>
        )}
      </div>
    </div>
  );
};
