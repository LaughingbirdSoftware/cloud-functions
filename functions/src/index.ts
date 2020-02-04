//import { request } from "http";

const functions = require('firebase-functions');
const admin = require('firebase-admin');
const cors = require('cors')({ origin: true });
//const rp = require('request-promise')

admin.initializeApp({
    credential: admin.credential.applicationDefault()
});
//  ["time"]: admin.firestore.FieldValue.serverTimestamp()
exports.autologin = functions.https.onRequest((req, res) => {
    cors(req, res, () => {
        const secret = 'hgdryKfs3jj234jfJGjsjjdfjjdj-qpzTksh7yg555sggPegdHhjhkjhs-88yysyOhjnLdnklWkkekrXjlwke';
        if (req.body["secret"] === secret) {
            const autoLoginKey = req.body["key"];
            const wpName = req.body["wpName"];
            const wpRole = req.body["wpRole"];
            const wpID = req.body["wpID"];

            const dapEmail = req.body["dapEmail"]; // Testing only!!!

            // console.log("autologin key: " + autoLoginKey);
            // console.log("autologin wpName: " + wpName);

            const afs = admin.firestore();
            // console.log("Cloud Function: autologin");
            // console.log("docID: " + autoLoginKey);
            // console.log("wpID: " + wpID);
            // console.log("wpName: " + wpName);
            // console.log("wpRole: " + wpRole);
            afs.collection('Autologin').doc(autoLoginKey).set({
                ["timeStamp"]: admin.firestore.FieldValue.serverTimestamp(),
                ["dapEmail"]: dapEmail,
                ["wpID"]: wpID,
                ["wpName"]: wpName,
                ["wpRole"]: wpRole,
                ["time"]: Date.now()
            }).then(() => {
                console.log("Set Autologin doc set() successful.");
                //console.log("with key: " + autoLoginKey);
                res.send("Set Autologin doc successful.");
            }).catch((error) => {
                console.log("Set Autologin doc failed: " + error);
                //res.send("");
                res.status(500).send(error)
            })
        }
    })
})
export const auth_maintenance = functions.pubsub
    .topic('trigger_auth_maintenance')
    .onPublish(async message => {
        // Triggered via Cloud Schedule.
        const maxDaysForConnection = 2;
        const maxMinutesForAutologin = 1;
        const afs = admin.firestore();
        const millisNow = Date.now();
        //console.log("millisNow: " + millisNow);
        // Delete Autologin documents on/after maxMinutesForAutologin.
        afs.collection("Autologin").get().then((querySnapshot) => {
            if (querySnapshot.empty) {
                console.log('No Autologin documents found.');
            } else {
                querySnapshot.forEach((doc) => {
                    const loginMillis: number = doc.data()["time"];
                    const wpName = doc.data()["wpName"];
                    if (wpName !== "localHostKey") {
                        console.log("Autologin: " + wpName);
                        const millisElapsed = millisNow - loginMillis;
                        const minutesElapsed = Math.floor(millisElapsed / (1000 * 60));
                        //console.log("Autologin minutesElapsed: " + minutesElapsed);
                        if (minutesElapsed >= maxMinutesForAutologin) {
                            // Delete the Autologin document.
                            doc.ref.delete().then(() => {
                                console.log("Autologin document successfully deleted.");
                            }).catch((error) => {
                                console.error("Error removing Autologin document: ", error);
                            });
                        }
                    }
                });
            }
        });
        // Delete Logins documents on/after maxDaysForConnection.
        afs.collection("Logins").get().then((querySnapshot) => {
            if (querySnapshot.empty) {
                console.log('No Logins documents found.');
            } else {
                querySnapshot.forEach((doc) => {
                    const key = doc.data()["key"];
                    console.log("Logins: " + key);
                    //const auth_uid = doc.data()["auth_uid"];
                    const loginMillis: number = doc.data()["time"];
                    //console.log("Logins loginMillis: " + loginMillis);
                    const millisElapsed = millisNow - loginMillis;
                    const minutesElapsed = Math.floor(millisElapsed / (1000 * 60));
                    const hoursElapsed = Math.floor(minutesElapsed / 60);
                    const daysElapsed = Math.floor(hoursElapsed / 24);
                    //console.log("Logins minutesElapsed: " + minutesElapsed);
                    //console.log("Logins hoursElapsed: " + hoursElapsed);
                    console.log("Logins daysElapsed: " + daysElapsed);
                    if (daysElapsed >= maxDaysForConnection) {
                        // Delete Logins doc.
                        doc.ref.delete().then(() => {
                            console.log("Logins document successfully deleted.");
                        }).catch((error) => {
                            console.error("Error removing Logins document: ", error);
                        });
                    }
                });
            }
        });
        // Delete authentications on/after maxDaysForConnection.
        // The result of this is that, after maxDaysForConnection,
        // if the user still has the app open, 
        // the user auth will expire in one hour, 
        // and the app will then fail to
        // load or store designs.
        listAllUsers(undefined);
    });
function listAllUsers(nextPageToken) {
    const millisNow = Date.now();
    const maxDaysForConnection = 2;
    admin.auth().listUsers(10, nextPageToken)
        .then((listUsersResult) => {
            listUsersResult.users.forEach((userRecord) => {
                //console.log("user", userRecord.toJSON());
                const authUID = userRecord.uid;
                const lastSignInTime = userRecord.metadata.lastSignInTime;
                const lastSignInMillis = Date.parse(lastSignInTime);
                const millisElapsed = millisNow - lastSignInMillis;
                const minutesElapsed = Math.floor(millisElapsed / (1000 * 60));
                const hoursElapsed = Math.floor(minutesElapsed / 60);
                const daysElapsed = Math.floor(hoursElapsed / 24);
                //console.log("auth lastSignInTime: ", lastSignInTime);
                //console.log("auth lastSignInMillis: " + lastSignInMillis);
                //console.log("auth minutesElapsed: " + minutesElapsed);
                console.log("auth " + authUID + "  daysElapsed: " + daysElapsed);
                if (daysElapsed >= maxDaysForConnection) {
                    admin.auth().deleteUser(userRecord.uid)
                        .then(() => {
                            console.log("Successfully deleted user");
                        })
                        .catch((error) => {
                            console.log("Error deleting user:", error);
                        });
                } else {
                    console.log("User not yet expired, so not deleted.")
                }
            });
            if (listUsersResult.pageToken) {
                setTimeout(() => {
                    listAllUsers(listUsersResult.pageToken);
                }, 2000);
            }
        })
        .catch(function (error) {
            console.log("Error listing users:", error);
        });
}
// ["report"]: JSON.stringify(res.body),
exports.electronCrashReport = functions.https.onRequest((req, res) => {
    cors(req, res, () => {
        //const Busboy = require('busboy');
        // For example of usage of busboy in cloud function:
        // https://cloud.google.com/functions/docs/writing/http
       
        // const minidump = require("minidump");

        console.log("electronCrashReport");
        const crashData = JSON.stringify(req.body);
        const afs = admin.firestore();
        afs.collection('ElectronCrashReport').doc().set({
            ["timeStamp"]: admin.firestore.FieldValue.serverTimestamp(),
            ["request"]: crashData,
            ["time"]: Date.now()
        }).then(() => {
            console.log("ElectronCrashReport set() successful.");
            res.send("ElectronCrashReport doc successful.");
        }).catch((error) => {
            console.log("Set ElectronCrashReport doc failed: " + error);
            res.status(500).send(error)
        })
    })
})