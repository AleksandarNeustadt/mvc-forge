# Cleanup - Obriši stare foldere

Sada kada smo reorganizovali strukturu, možete obrisati prazne/duplirane foldere:

## Folderi za brisanje:

```bash
# Prazni folder (Model.php i User.php su obrisani)
rm -rf core/models

# Duplirani fajlovi (sve je već u mvc/)
rm -rf core/controllers
rm -rf core/views
```

## Finalna struktura:

```
core/
  └── classes/
      └── mvc/
          ├── Controller.php  ✅ Base Controller klasa
          └── Model.php       ✅ Base Model klasa

mvc/
  ├── controllers/    ✅ Vaši kontroleri (nasleđuju Controller)
  ├── models/         ✅ Vaši modeli (nasleđuju Model)
  └── views/          ✅ Vaši view fajlovi
```

Sve putanje u kodu su već ažurirane! 🎉

